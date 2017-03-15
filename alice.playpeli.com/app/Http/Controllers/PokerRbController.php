<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Input;

use Redis;
use App\Helpers\CmdHelper;
use App\Helpers\UtilsHelper;
use App\Helpers\PokerRbHelper;
use App\Helpers\GameHelper;
use App\Helpers\ChatHelper;
use App\Helpers\TestHelper;
use App\Models\PokerRbModel;
use App\Models\CustomerModel;
use App\Models\ProductModel;
use App\Models\CartModel;
use App\Models\LogModel;
use App\Models\WalletModel;

class PokerRbController extends Controller
{
	
	function anyCurrentList(){
		$pokerRbs = PokerRbModel::getCurrentList();
		$result = json_encode($pokerRbs, JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
		$response = Response::make($result, 200);
		$response->header('Content-Type', 'text/html');
		return $response;
	}
	
	function anyGetPokerRb()
	{
		$pokerRbId = Input::get('id','0');
		$pokerRbs = PokerRbModel::getPokerRb($pokerRbId);
	
		if(count($pokerRbs)>0)
		{
			$pokerRb = $pokerRbs[0];
			$result = json_encode($pokerRb, JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
		}
		else
		{
			$result = "{\"result\":0}";
		}
	
		$response = Response::make($result, 200);
		$response->header('Content-Type', 'text/html');
		return $response;
	}
	
	
// 	function anyBet()
// 	{
// 		$pokerRbId = Input::get('id','0');
// 		$bet = Input::get('bet','0');
// 		$pokerRbs = PokerRbModel::bet($pokerRbId,$bet);
	
// 		if(count($pokerRbs)>0)
// 		{
// 			$pokerRb = $pokerRbs[0];
// 			$result = json_encode($pokerRb, JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
// 		}
// 		else
// 		{
// 			$result = "{\"result\":0}";
// 		}
	
// 		$response = Response::make($result, 200);
// 		$response->header('Content-Type', 'text/html');
// 		return $response;
// 	}
	
	
	function anyStart()
	{
		$cachekey = CmdHelper::CACHE_POKER_RB_PREFIX.".status";
		$status = Redis::connection('pokerrb')->get($cachekey);
	
		if($status=="")
			$status="OFF";
	
			if($status=="OFF")
			{
				ignore_user_abort(); //即使Client断开(如关掉浏览器)，PHP脚本也可以继续执行.
				set_time_limit(0); // 执行时间为无限制，php默认的执行时间是30秒，通过set_time_limit(0)可以让程序无限制的执行下去
				$interval=1; // 每隔1秒钟运行
	
				Redis::connection('pokerrb')->set($cachekey,"ON");
	
				do{
					PokerRbHelper::Tick();
					 
					$status = Redis::connection('pokerrb')->get($cachekey);
					if($status=="OFF")
						break;
	
					sleep($interval); // 按设置的时间等待1秒循环执行
				}while(true);
			}
			else
			{
				echo "Poker RB Service already on!<br>";
			}
			echo "Current status:$status";
	}
	
	function anyPause()
	{
		$cachekey = CmdHelper::CACHE_POKER_RB_PREFIX.".status";
		$status =Redis::connection('pokerrb')->getset($cachekey,"OFF");
		echo "Current status:$status -> OFF";
	}

	function anyStop()
	{
		$cachekey = CmdHelper::CACHE_POKER_RB_PREFIX.".status";
		$resetCacheKey = CmdHelper::CACHE_POKER_RB_PREFIX.".reset.status";
		$status = Redis::connection('pokerrb')->get($cachekey);
		//         echo "Current status:$status";
		Redis::connection('pokerrb')->flushdb();
		Redis::connection('pokerrb')->set($cachekey,"OFF");
		Redis::connection('pokerrb')->set($resetCacheKey,"1");
		// exec('service php-fpm restart');
		PokerRbHelper::Unlock("rooms");
		PokerRbHelper::Unlock("bettings");
		// PokerRbHelper::Reset();
		echo "1";
	}
	
	function anyReset()
	{
		$cachekey = CmdHelper::CACHE_POKER_RB_PREFIX.".status";
		$resetCacheKey = CmdHelper::CACHE_POKER_RB_PREFIX.".reset.status";
		$status = Redis::connection('pokerrb')->get($cachekey);
		//         echo "Current status:$status";
		Redis::connection('pokerrb')->flushdb();
		Redis::connection('pokerrb')->set($cachekey,"OFF");
		Redis::connection('pokerrb')->set($resetCacheKey,"1");
		// exec('service php-fpm restart');
		PokerRbHelper::Unlock("rooms");
		PokerRbHelper::Unlock("bettings");
		// PokerRbHelper::Reset();
		echo "1";
	}
	
	function anyCheck()
	{
		$cachekey = CmdHelpr::CACHE_WAR_PREFIX.".status";
		$status = Redis::connection('pokerrb')->get($cachekey);
		echo "Current status:$status<br>";
	}
	
	function anyGame()
	{
		$roomId = Input::get('roomid','0');
		if($roomId>0)
		{
			$rooms = PokerRbHelper::GetRooms();
			echo "Rooms num:".count($rooms)."<br>";
			$room = PokerRbHelper::GetRoom($roomId);
			$cards = PokerRbHelper::GetCards($room->roomId);
			$bettings = PokerRbHelper::GetBettings($room->roomId);
			echo json_encode($room,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK)."<br>";
			echo json_encode($cards,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK)."<br>";
			echo json_encode($bettings,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK)."<br>";
			//PokerRbHelper::SetCards($room->roomId,$cards);
			//PokerRbHelper::SetBettings($room->roomId,$bettings);
			echo "Rooms num:".count($rooms)."<br>";
		}
		else
		{
			$rooms = PokerRbHelper::GetRooms();
			echo "Rooms num:".count($rooms)."<br>";
			
			foreach ($rooms as $roomId)
			{
				$room = PokerRbHelper::GetRoom($roomId);
				$cards = PokerRbHelper::GetCards($roomId);
				$bettings = PokerRbHelper::GetBettings($roomId);
				echo json_encode($room,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK)."<br>";
				echo json_encode($cards,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK)."<br>";
				echo json_encode($bettings,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK)."<br>";
			}
			//PokerRbHelper::SetCards($room->roomId,$cards);
			//PokerRbHelper::SetBettings($room->roomId,$bettings);
			echo "Rooms num:".count($rooms)."<br>";
		}
	}
	
	
	function anyCreateRoom()
	{
		$roomId = Input::get('roomid','0');
		$roomMode = Input::get('mode','0');
	
		PokerRbHelper::Create($roomId,$roomMode);
	
		$result = "{\"result\":1}";
	
		$response = Response::make($result, 200);
		$response->header('Content-Type', 'text/html');
		return $response;
	}
		
	function anySwitchMode()
	{
		$roomId = Input::get('roomid','0');
		$roomMode = Input::get('mode','0');
	
		PokerRbHelper::Lock("room.$roomId");
		$room = PokerRbHelper::GetRoom($roomId);
		$room->mode = $roomMode;
		$room->version++;
		$room->time = time();
		PokerRbHelper::SetRoom($room->roomId,$room);
		PokerRbHelper::Unlock("room.$roomId");
		$result = "{\"result\":1,\"mode\":$roomMode}";
		
		if($roomMode == PokerRbHelper::MOD_DEAL_MANUAL)
			Log::info("anySwitchMode switch mode to MOD_DEAL_MANUAL");
		else if($roomMode == PokerRbHelper::MOD_DEAL_AUTO)
			Log::info("anySwitchMode switch mode to MOD_DEAL_AUTO");
		else
			Log::info("anySwitchMode switch mode to MOD_DEAL_HALF");
		
		$response = Response::make($result, 200);
		$response->header('Content-Type', 'text/html');
		return $response;
	}
	
	function anyEnterRoom()
	{
		$roomId = Input::get('roomid','0');
		$roomMode = Input::get('mode','0');
		$roomType = 2;
		$cid = Input::get('cid','0');
		
		PokerRbHelper::Enter($roomId,$roomMode, $cid);
		$customers = CustomerModel::getCustomers($cid);
		if(count($customers)>0)
		{
			$logId=LogModel::createloginRoomLog($cid,$roomId,$roomType);
			GameHelper::increasewachnum($roomId);
			$customer = $customers[0];
			$customer->result = 1;
			$customer->logid = $logId[0]->log_id;
			$customer->gems = WalletModel::balance($cid);
			ChatHelper::Say($roomId, $cid, "系统", "[".$customer->nickname."]进入房间！");
			
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
	
	function anyDeal()
	{
		$roomId = Input::get('roomid','0');
		$card = Input::get('card','0');
		$room = PokerRbHelper::GetRoom($roomId);
		if($room->state==PokerRbHelper::STATE_WAIT_CARD||1)
		{   
			PokerRbHelper::Lock("room.$roomId");
            $cards = PokerRbHelper::GetCards($room->roomId);
		    $color = PokerRbHelper::Color(PokerRbHelper::Deal($cards,$card));
			$room->balance+=PokerRbHelper::Settle($room,$card,$color);
			PokerRbHelper::SetCards($room->roomId,$cards);
			$room->version++;
			$room->round++;
			if($room->round%15=="0")//Minimum bet increase by ¥0.10 every 15 cards
			{
				$room->bets+=0.1;
			}
			
			$room->time = time();
			PokerRbHelper::SetRoom($room->roomId,$room);
			PokerRbHelper::Unlock("room.$roomId");
			
			LogModel::createDealingLog(1,$room->roomId,$room->round,$card,(int)$room->balance);
			
			$result = "{\"result\":1}";
		}
		else
		{
            $result = "{\"result\":0}";
		}
		
		$result = "{\"result\":1}";
		$response = Response::make($result, 200);
		$response->header('Content-Type', 'text/html');
		return $response;
	}

	function anyShuffle()
	{
		$roomId = Input::get('roomid','0');
	
		PokerRbHelper::Lock("room.$roomId");
		$room = PokerRbHelper::GetRoom($roomId);
		$cards = PokerRbHelper::GetCards($room->roomId);
		PokerRbHelper::Shuffle($cards);
		$room->round=1;

		PokerRbHelper::SetCards($room->roomId,$cards);
		PokerRbHelper::Finish($room);
		
		$room->time = time();
		$room->state = PokerRbHelper::STATE_WAIT_BETTING;
		$room->timeLimit = time()+PokerRbHelper::TIME_LIMIT_BET;
		$room->version++;
		
		Log::info("anyShuffle state has changed to STATE_WAIT_BETTING,roomId[$room->roomId]!");
		
		PokerRbHelper::SetRoom($room->roomId,$room);
		PokerRbHelper::Unlock("room.$roomId");
		
		
		$result = "{\"result\":1}";
		$response = Response::make($result, 200);
		$response->header('Content-Type', 'text/html');
		return $response;
	}	
	
	
	function anyBet()
	{
		$roomId = Input::get('roomid','0');
		$cid = Input::get('cid','0');
		$color = Input::get('color','0');
		$bet = Input::get('bet','0');

		PokerRbHelper::Lock("room.$roomId");
		$room = PokerRbHelper::GetRoom($roomId);
		if($room->timeLimit>0)
		{
			$result=PokerRbHelper::Bet($room,$cid,$color,$bet);
		}
		else
		{
			$result = new \stdClass();
			$result->result = 0;
		}
		PokerRbHelper::Unlock("room.$roomId");
		$result = json_encode($result, JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
		$response = Response::make($result, 200);
		$response->header('Content-Type', 'text/html');
		return $response;
	}
	
	function anyCashOut()
	{
		$roomId = Input::get('roomid','0');
		$cid = Input::get('cid','0');
		$room = PokerRbHelper::GetRoom($roomId);
		$result = PokerRbHelper::CashOut($room,$cid);
		
		if($result&&$result->result==1)
		{
			$room->version++;
			PokerRbHelper::SetRoom($room->roomId, $room);
		}
		
		$result = json_encode($result, JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);		
		$response = Response::make($result, 200);
		$response->header('Content-Type', 'text/html');
		return $response;
	}
	
	function anyTest()
	{
		PokerRbHelper::Lock("test");
		echo "start time:".date('y-m-d h:i:s',time())."<br>";
	
		for($i=0;$i<10;$i++)
		{
			Redis::connection('pokerrb')->set("test",$i);
			sleep(1);
			echo Redis::connection('pokerrb')->get("test")."<br>";
		}
	
		echo "end time:".date('y-m-d h:i:s',time())."<br>";
		PokerRbHelper::Unlock("test");
	}
	public function anyAddToCart()
	{
	    $roomId = Input::get('roomid','0');
	    $cid = Input::get('cid','0');
	    $productId = Input::get('productid','0');
	    $result = new \stdClass();
	    $result->result = PokerRbHelper::AddToCart($roomId,$cid,$productId);
	    $result = json_encode($result, JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
	    $response = Response::make($result, 200);
	    $response->header('Content-Type', 'text/html');
	    return $response;
	     
	}
	
	public function anyUnlock()
	{
	    $key = Input::get('key','');
	    PokerRbHelper::Unlock($key);
	    $result = new \stdClass();
	    $result->result = 1;
	    $result = json_encode($result, JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
	    $response = Response::make($result, 200);
	    $response->header('Content-Type', 'text/html');
	    return $response;
	}

	//后台控制游戏是否开启
	
	//后台控制游戏是否开启
	//开启游戏
	public  function  anyPokerRbEnabled()
	{
	    $id=Input::get('poker_id',0);
	    $duration=Input::get("duration",600);
	    
	    Log::info("PokerRb Enabled id[$id] duration[$duration]");
	    
	    $pokerrb=PokerRbModel::PokerRbEnabled($id,$duration);
	
	    if($pokerrb && count($pokerrb)>0)
	    {
	        $poker = $pokerrb[0];
	        PokerRbHelper::StartPokerRb($poker->room_id, $poker);
	    }
	
	    return json_encode($poker);
	
	}
	//关闭游戏
	public  function  anyPokerRbDisabled()
	{
	    $id=Input::get('poker_id',0);
	    
	    Log::info("PokerRb Disabled id[$id]");
	    
	    $pokerrb=PokerRbModel::PokerRbDisabled($id);
	    $poker=new \stdClass();
	    if($pokerrb && count($pokerrb)>0)
	    {
	        $poker = $pokerrb[0];
	        PokerRbHelper::StopPokerRb($poker->room_id);
	    }
	
	    return json_encode($poker);
	}
	
	//开启游戏
	public  function  anyPokerRbEnableds()
	{
	    $id=Input::get('poker_id',0);//排班id
	    $duration=Input::get("duration",600);//开始时间与结束时间的差
	    return PokerRbHelper::pokerRbEnabled($id,$duration);
	}
    //关闭游戏
    public  function  anyPokerRbDisableds()
    {
      $id=Input::get('poker_id',0);//排班id
      return PokerRbHelper::pokerRbDisabled($id);
    }

    //自动开启
    public function anyAutoPokerRb()
    {
       TestHelper::AutoPokerRb();
    }
	
}