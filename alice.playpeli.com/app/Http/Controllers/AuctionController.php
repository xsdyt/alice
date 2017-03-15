<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Session;

use App\Helpers\UtilsHelper;
use App\Helpers\CmdHelper;
use App\Helpers\RedisHelper;
use App\Helpers\AuctionHelper;
use App\Models\AuctionModel;
use App\Models\DealerModel;
use App\Models\OrdersModel;
use App\Models\ProductModel;
use App\Models\CustomerModel;
use App\Models\AdministratorModel;
use App\Models\LogModel;
use App\Models\WalletModel;

class AuctionController extends Controller
{
    public function __construct()
    {
   //      $this->middleware('auth.manager');
    }
    
	function anyCurrentList(){
		$auctions = AuctionModel::getCurrentList();
		$result = json_encode($auctions, JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
		$response = Response::make($result, 200);
		$response->header('Content-Type', 'text/html');
		return $response;
	}

	function anyGetAuctionsEnabled()
	{
		$roomId = Input::get('roomid','0');
		$auctions = AuctionModel::getAuctionsEnabled($roomId);
		$result = json_encode($auctions, JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
		$response = Response::make($result, 200);
		$response->header('Content-Type', 'text/html');
		return $response;
	}
	
	
	function anyGetAuction()
	{
		$auctionId = Input::get('auctionid','0');
		$auctions = AuctionModel::getAuction($auctionId);
		
		if(count($auctions)>0)
		{
			$auction = $auctions[0];
			$result = json_encode($auction, JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
		}
		else
		{
			$result = "{\"result\":0}";
		}
		
		$response = Response::make($result, 200);
		$response->header('Content-Type', 'text/html');
		return $response;
	}
	
	
	function anyBid()
	{
		$roomId = Input::get('roomid','0');
		$cid = Input::get('cid','0');
		$bid = Input::get('bid','0');
// 		$auctions = AuctionModel::bid($auctionId,$bid);
		
// 		if(count($auctions)>0)
// 		{
// 			$auction = $auctions[0];
// 			$result = json_encode($auction, JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
// 		}
// 		else
// 		{
// 			$result = "{\"result\":0}";
// 		}
		$result = "{\"result\":0}";
		$room = AuctionHelper::Bid($roomId, $cid, $bid);
			
		if($room && $room->auction)
		{
			$result = json_encode($room->auction, JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
		}
		
		$response = Response::make($result, 200);
		$response->header('Content-Type', 'text/html');
		return $response;		
		
	}

	function anyStart()
	{
		$cachekey = CmdHelper::CACHE_AUCTION_PREFIX.".status";
		$status = Redis::connection('auction')->get($cachekey);
	
		if($status=="")
			$status="OFF";
	
			if($status=="OFF")
			{
				ignore_user_abort(); //即使Client断开(如关掉浏览器)，PHP脚本也可以继续执行.
				set_time_limit(0); // 执行时间为无限制，php默认的执行时间是30秒，通过set_time_limit(0)可以让程序无限制的执行下去
				$interval=1; // 每隔1秒钟运行
	
				Redis::connection('auction')->set($cachekey,"ON");
	
				do{
					AuctionHelper::Tick();
	
					$status = Redis::connection('auction')->get($cachekey);
					if($status=="OFF")
						break;
	
						sleep($interval); // 按设置的时间等待1秒循环执行
				}while(true);
			}
			else
			{
				echo "Auction Service already on!<br>";
			}
			echo "Current status:$status";
	}
	
	function anyPause()
	{
		$cachekey = CmdHelper::CACHE_AUCTION_PREFIX.".status";
		$status =Redis::connection('auction')->getset($cachekey,"OFF");
		echo "Current status:$status -> OFF";
	}
	
	function anyStop()
	{
		$cachekey = CmdHelper::CACHE_AUCTION_PREFIX.".status";
		$resetCacheKey = CmdHelper::CACHE_AUCTION_PREFIX.".reset.status";
		$status = Redis::connection('auction')->get($cachekey);
		//         echo "Current status:$status";
		Redis::connection('auction')->flushdb();
		Redis::connection('auction')->set($cachekey,"OFF");
		Redis::connection('auction')->set($resetCacheKey,"1");
		// exec('service php-fpm restart');
		AuctionHelper::Unlock("rooms");
		// AuctionHelper::Reset();
		echo "1";
	}
	
	function anyGame()
	{
		$roomId = Input::get('roomid','0');
		if($roomId>0)
		{
			$rooms = AuctionHelper::GetRooms();
			echo "Rooms num:".count($rooms)."<br>";
			$room = AuctionHelper::GetRoom($roomId);
			echo json_encode($room,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK)."<br>";
			//AuctionHelper::SetCards($room->roomId,$cards);
			//AuctionHelper::SetBettings($room->roomId,$bettings);
			echo "Rooms num:".count($rooms)."<br>";
		}
		else
		{
			$rooms = AuctionHelper::GetRooms();
			echo "Rooms num:".count($rooms)."<br>";
				
			foreach ($rooms as $roomId)
			{
				$room = AuctionHelper::GetRoom($roomId);
				echo json_encode($room,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK)."<br>";
			}
			//AuctionHelper::SetCards($room->roomId,$cards);
			//AuctionHelper::SetBettings($room->roomId,$bettings);
			echo "Rooms num:".count($rooms)."<br>";
		}
	}
	
	function anyEnterRoom()
	{
		$roomId = Input::get('roomid','0');
		$cid = Input::get('cid','0');
	
		AuctionHelper::Enter($roomId, $cid);
	
		$customers = CustomerModel::getCustomers($cid);
		if(count($customers)>0)
		{
			$customer = $customers[0];
			$customer->result = 1;
			$customer->gems = WalletModel::balance($cid);
			$result = json_encode($customer, JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
		}
		else
		{
			$result = "{\"result\":0}";
		}
	
		$response = Response::make($result, 200);
		$response->header('Content-Type', 'text/html');
		return $response;
	}
	
	function anyStartAuction()
	{
		$roomId = Input::get('roomid','0');
		$auctionId = Input::get('auctionid','0');
		
		$auctions = AuctionModel::setAuctionsEnabled($auctionId,$roomId,600);
		
		if($auctions && count($auctions)>0)
		{
			$auction = $auctions[0];
			AuctionHelper::StartAuction($roomId, $auction);
		}
		echo 1;
	}

	function anyStopAuction()
	{
		$roomId = Input::get('roomid','0');	
		AuctionHelper::StopAuction($roomId);
		$auctions = AuctionModel::getAuctionsEnabled($roomId);
		
		if($auctions && count($auctions)>0)
		{
			$auction = $auctions[0];
			AuctionModel::setAuctionsdisbled($auctionId);
		}
			
		echo 1;
	}
	
	//开始
  //ajax处理界面刷新而不改变值==1
  public  function  anyAuctionEnabled()
  {
      $id=Input::get('auction_id',0);
      $duration=Input::get("duration",600);
     // Log::info("Auction Enabled id[$id] duration[$duration]");
      // echo $id;exit;
      $auctions=AuctionModel::AuctionEnabled($id,$duration);
      
      if($auctions && count($auctions)>0)
      {
      	$auction = $auctions[0];
      	AuctionHelper::StartAuction($auction->room_id, $auction);
      }
      
      return json_encode($auction);
  
  }
  //结束
  //ajax处理界面刷新而不改变值==0
  public  function  anyAuctionDisabled()
  {
      $id=Input::get('auction_id',0);
      Log::info("Auction Disabled id[$id]");
      $auctions=AuctionModel::AuctionDisabled($id);

      if($auctions && count($auctions)>0)
      {
		$auction = $auctions[0];
		AuctionHelper::StopAuction($auction->room_id);
      }
      
      return json_encode($auction);
  
  }

  function anyTest1()
  {
  	//AuctionHelper::AutoAuction();
  	$a=AuctionHelper::TickRoom(1, 642);
  	print_r(json_encode($a));
  }
  

  
}