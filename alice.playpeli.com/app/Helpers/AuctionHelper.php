<?php
namespace App\Helpers;

use Redis;
use App\Helpers\CmdHelper;
use App\Models\CustomerModel;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use App\Models\LobbyModel;
use App\Models\CartModel;
use App\Models\AuctionModel;

class AuctionHelper
{
	const AUCTION_END=1;//结束
	const AUCTION_RECHARGE=2;//被购买
	const AUCTION_START=0;//进行中
	public static function Lock($resource,$expire=5,$sleep=20000)
	{
		$cachekey = CmdHelper::CACHE_AUCTION_PREFIX.".lock.".$resource;
		while(Redis::connection('auction')->setnx($cachekey,microtime(true))!=1)
		{
			$timestamp1 = Redis::connection('auction')->get($cachekey);
				
			if(microtime(true)-$timestamp1>$expire)		//过期
			{
				$timestamp2 = Redis::connection('auction')->getset($cachekey,microtime(true));
				if($timestamp1==$timestamp2)	//如果检测时与设置时值相同,期间没有其他线程获取锁,所以成功获得锁
				{
					Log::info("AuctionHelper::Lock timeout,force get lock success key[$cachekey] resource[$resource] timestamp1[$timestamp1] timestamp2[$timestamp2]");
					break;
				}
				else 
				{
					Log::info("AuctionHelper::Lock timeout,force get lock failed key[$cachekey] resource[$resource] timestamp1[$timestamp1] timestamp2[$timestamp2]");
				}
				//Redis::connection('auction')->expire($cachekey,$expire+1);
			}
			usleep($sleep);
		}
	}
	
	public static function Unlock($resource)
	{
		$cachekey = CmdHelper::CACHE_AUCTION_PREFIX.".lock.".$resource;
		Redis::connection('auction')->del($cachekey);
	}
	
	
	public static function Reset()
	{
		$rooms = self::GetRooms();
		foreach ($rooms as $roomId){
			
			self::Lock("room.$roomId");
			$cachekey = CmdHelper::CACHE_AUCTION_PREFIX.".room.".$roomId;
			Redis::connection('auction')->set($cachekey,"");
			
			$cachekey = CmdHelper::CACHE_AUCTION_PREFIX.".cards.".$roomId;
			Redis::connection('auction')->set($cachekey,"");
				
			$cachekey = CmdHelper::CACHE_AUCTION_PREFIX.".bettings.".$roomId;
			Redis::connection('auction')->set($cachekey,"");
			self::Unlock("room.$roomId");
		}
	
		$cachekey = CmdHelper::CACHE_AUCTION_PREFIX.".rooms";
		Redis::connection('auction')->set($cachekey,"");
	}
	
	
	public static function Tick()
	{
		ServiceHelper::UpdateServiceTime("AUCTION.TICK");
		$startTime=microtime(true);
		//Log::info('tick start time '.$startTime);

		$rooms = self::GetRooms();
		foreach ($rooms as $roomId)
		{
			self::Lock("room.$roomId");
			$room = self::GetRoom($roomId);
			if($room)
			{
				if(isset($room->auction) && $room->auction!=null)
				{
					
                    $room->auction->countdown = $room->auction->end_timestamp-time();
					if($room->auction->countdown<=0)
					{
                       $room->auction->countdown=0;
					}

					if($room->auction->countdown<=0&&$room->state==self::AUCTION_START)
					{
						//$room->auction->countdown = 0;
					    $room->state=self::AUCTION_END;
					    $room->version++;
					}
					if($room->auction->countdown<=0&&$room->state==self::AUCTION_END&&$room->auction->id)
					{
						if($room->auction->winner_id>0)
				    	{
                        	CartModel::addProductToCart($room->auction->winner_id,$room->auction->product_id,$room->auction->highest_bid,0,1);
                        	Log::info("auction add producttocart winner_id[".$room->auction->winner_id."] product_id[".$room->auction->product_id."] highest_bid[".$room->auction->highest_bid."] state[".$room->state."]");
                        	$room->state=self::AUCTION_RECHARGE;
                        	//$room->auction->id=0;
                        	$room->version++;
				    	}
					}
					/*if($room->auction->countdown<=0&&$room->state==self::AUCTION_RECHARGE)
					{
						//$room->state=self::AUCTION_END;
					}*/	
				}
				self::SetRoom($roomId,$room);
			}
			self::Unlock("room.$roomId");
		}
		
		$endTime=microtime(true);
		$resultTime=$endTime-$startTime;
		if($resultTime>=3){
			Log::info('Auction Helper tick time start ['.$startTime.'[end '.$endTime.'] result['.$resultTime);
		}
	}

	public static function GetRooms()
	{
		$cachekey = CmdHelper::CACHE_AUCTION_PREFIX.".rooms";
		$strRooms = Redis::connection('auction')->get($cachekey);
		$rooms = json_decode($strRooms);
		if(!is_array($rooms))
			$rooms = Array();
		return $rooms;
	}
	
	public static function SetRooms($rooms)
	{
		$cachekey = CmdHelper::CACHE_AUCTION_PREFIX.".rooms";
		$strRooms = json_encode($rooms,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
		Redis::connection('auction')->set($cachekey,$strRooms);
	}

	public static function ExistRoom(&$rooms,$checkRoomId)
	{
		foreach ($rooms as $roomId){
			if($roomId == $checkRoomId)
				return true;
		}
		return false;
	}

	public static function GetRoom($roomId)
	{
		$cachekey = CmdHelper::CACHE_AUCTION_PREFIX.".room.".$roomId;
		$strRoom = Redis::connection('auction')->get($cachekey);
		$room = json_decode($strRoom);
		
		if(!is_object($room))
		{
			$room = new \stdClass();
			$room->roomId = $roomId;
			$room->time = time();
			$room->state = 0;//1结束 2 被购买
			$room->version=1;

			self::Lock("rooms");
			
			$rooms = self::GetRooms();
			
			if(!self::ExistRoom($rooms,$roomId))
			{
				array_push($rooms,$roomId);
				self::SetRooms($rooms);
			}
			
			self::Unlock("rooms");
		}
		return $room;
	}

	public static function SetRoom($roomId,$room)
	{
		$cachekey = CmdHelper::CACHE_AUCTION_PREFIX.".room.".$roomId;
		$strRoom = json_encode($room,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
		Redis::connection('auction')->set($cachekey,$strRoom);
	}
	
	public static function StartAuction($roomId,$auction)
	{
		if($roomId>0 && $auction && $auction->countdown>0)
		{
			self::Lock("room.$roomId");
			$room = self::GetRoom($roomId);
			if($room)
			{
				$room->auction = $auction;
				$room->state=0;
				$room->auction->end_timestamp = time()+$auction->countdown;
				self::SetRoom($roomId, $room);
			}
			self::Unlock("room.$roomId");
		}
	}
	
	public static function StopAuction($roomId)
	{
		if($roomId>0)
		{
			self::Lock("room.$roomId");
			$room = self::GetRoom($roomId);
			if($room)
			{
				if(isset($room->auction) && $room->auction!=null)
					$room->auction->id = 0;
				self::SetRoom($roomId, $room);
			}
			self::Unlock("room.$roomId");
		}
		
	}
	
	public static function TickRoom($roomId,$cid)
	{
		if($roomId>0)
		{
			$room = self::GetRoom($roomId);
			if($room)
			{
				$cachekey = CmdHelper::CACHE_AUCTION_PREFIX.".room.version.".$cid;
				$version = Redis::connection('auction')->get($cachekey);
				if($version==null || $version=="")
					$version = 1;
	
				if($room->version!=$version)
				{
					$version = $room->version;
					Redis::connection('auction')->set($cachekey,$version);
				}
			   
				$room->result = 1;
				$room->last_tick = microtime(true);
				
				return $room;
			}
			
		}
		return null;
	}
	
	public static function Enter($roomId,$cid)
	{
		$cachekey = CmdHelper::CACHE_AUCTION_PREFIX.".room.version.".$cid;
		$version = Redis::connection('auction')->set($cachekey,0);
		
		$room = self::GetRoom($roomId);
		self::SetRoom($roomId, $room);
	}

	public static function Bid($roomId,$cid,$bid)
	{
		if($roomId>0)
		{
			self::Lock("room.$roomId");
			$room = self::GetRoom($roomId);
			if($room)
			{
				if(isset($room->auction) && $room->auction!=null && $bid>$room->auction->highest_bid && $room->auction->countdown>0 && $room->auction->winner_id != $cid)
				{
					$room->auction->highest_bid = $bid;
					$room->auction->winner_id = $cid;
					self::SetRoom($roomId, $room);
					$room->auction->result=1;
				}
				else
				{
					$room->auction->result=0;
				}
			}
			self::Unlock("room.$roomId");
		}
		
		return $room;
	}

	//开始
  public  static function  ActionEnabled($id,$duration=600)
  {
      Log::info("Auction Enabled id[$id] duration[$duration]");
      $auctions=AuctionModel::AuctionEnabled($id,$duration);
      
      if($auctions && count($auctions)>0)
      {
      	$auction = $auctions[0];
      	AuctionHelper::StartAuction($auction->room_id, $auction);
      }
      
      return json_encode($auction);
  
  }

  //自动开始
  public  static function  AutoAuctionEnabled($id,$duration=600)
  {
      Log::info("AutoAuction Enabled id[$id] duration[$duration]");
      $auctions=AuctionModel::AuctionEnabled($id);
      
      if($auctions && count($auctions)>0)
      {
      	$auction = $auctions[0];
      	AuctionHelper::StartAuction($auction->room_id, $auction);
      }
      
      return json_encode($auction);
  
  }

  
  //结束
  public  static function  AuctionDisabled($id)
  {
      Log::info("Auction Disabled id[$id]");
      $auctions=AuctionModel::AuctionDisabled($id);

      if($auctions && count($auctions)>0)
      {
		$auction = $auctions[0];
		AuctionHelper::StopAuction($auction->room_id);
      }
      
      return json_encode($auction);
  
  }

    //自动执行
    public static function AutoAuction()
    {
       $scheduleInfo=LobbyModel::getSchedule(1);
	   Log::info('AutoPokerRb [=]'.json_encode($scheduleInfo));
	   // echo "<pre>";
	   // print_r($scheduleInfo);exit;
	   if(count($scheduleInfo)>0)
	   {
           foreach ($scheduleInfo as $key => $value)
		   {
		   	    if($value->is_line)
		   	    {
				    $room = self::GetRoom($value->room_id);
				    if($room->auction->end_time<date('Y-m-d H:i:s',time()))
				    {
				    	$goods=explode(',',$value->activitys_id);
				    	for($i=0;$i<count($goods);$i++)
				    	{
		                    $auction=AuctionModel::getAuction($goods[$i]);
		                    if(count($auction)>0)
		                    {
                                if(!$auction[0]->enabled&&$auction[0]->end_time!='0000-00-00 00:00:00')
			                    {
			                    	Log::info('AutoPokerRb start pokerid='.$goods[$i]);
			                        self::AutoAuctionEnabled($goods[$i]);
			                        break;
			                    }
		                    }
		                 }   
				    }
			   	}
			}
	    }
    }
	
}