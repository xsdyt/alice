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
use App\Helpers\ServiceHelper;
use App\Helpers\SocketHelper;
use App\Models\OrdersModel;
use App\Models\DealerModel;
use App\Models\CustomerModel;
use App\Models\ProductModel;
use App\Models\LogModel;
use App\Models\CartModel;
use CURLFile;

class ServiceController extends Controller
{
	const TIME_OUT = 10;			//超时
	
	function  anyMonitor(){
		$currentTime = time();
		$stateObject = new \stdClass();
		
		$stateObject->POKERRB_TICK = ServiceHelper::GetServiceTime("POKERRB.TICK");
		$elaspe = $currentTime-$stateObject->POKERRB_TICK;
		if($stateObject->POKERRB_TICK!=null && $elaspe<=self::TIME_OUT)
			$stateObject->POKERRB_TICK = "ON";
		else 
			$stateObject->POKERRB_TICK = "OFF(".$elaspe.")";
		
		$stateObject->AUCTION_TICK = ServiceHelper::GetServiceTime("AUCTION.TICK");
		$elaspe = $currentTime-$stateObject->AUCTION_TICK;
		if($stateObject->AUCTION_TICK!=null && $elaspe<=self::TIME_OUT)
			$stateObject->AUCTION_TICK = "ON";
		else
			$stateObject->AUCTION_TICK = "OFF(".$elaspe.")";
		
		$socketList = SocketHelper::GetSocketList();
		
		if(is_array($socketList))
		{
			foreach ($socketList as $key => $socketInfo) {
				$attrib = "SOCKET_".$socketInfo->port;
				$stateObject->$attrib = ServiceHelper::GetServiceTime("SOCKET.".$socketInfo->address.".".$socketInfo->port);
				$elaspe = $currentTime-$stateObject->$attrib;
				if($stateObject->$attrib!=null && $elaspe<=self::TIME_OUT)
					$stateObject->$attrib = "ON";
				else
					$stateObject->$attrib = "OFF(".$elaspe.")";
			}
		}

		$result = json_encode($stateObject, JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
		$response = Response::make($result, 200);
		$response->header('Content-Type', 'text/html');
		return $response;
	}
	
	function  anyReset(){
		ServiceHelper::Reset();
	}

	function  anyConsole()
	{
		
		$currentTime = time();
		$stateObject = new \stdClass();
		
		$stateObject->POKERRB_TICK = ServiceHelper::GetServiceTime("POKERRB.TICK");
		$elaspe = $currentTime-$stateObject->POKERRB_TICK;
		if($stateObject->POKERRB_TICK!=null && $elaspe<=self::TIME_OUT)
		{
			$stateObject->POKERRB_TICK = true;
		}
		else
		{
			$stateObject->POKERRB_TICK = false;
			$stateObject->POKERRB_TICK_ELAPSE = $elaspe;
		}
		
		$stateObject->AUCTION_TICK = ServiceHelper::GetServiceTime("AUCTION.TICK");
		$elaspe = $currentTime-$stateObject->AUCTION_TICK;
		if($stateObject->AUCTION_TICK!=null && $elaspe<=self::TIME_OUT)
		{
			$stateObject->AUCTION_TICK = true;
		}
		else
		{
			$stateObject->AUCTION_TICK = false;
			$stateObject->AUCTION_TICK_ELAPSE = $elaspe;
		}
		
		$socketList = SocketHelper::GetSocketList();
		
		if(is_array($socketList))
		{
			foreach ($socketList as $key => $socketInfo) {
				$attrib = "SOCKET_".$socketInfo->port;
				$stateObject->$attrib = ServiceHelper::GetServiceTime("SOCKET.".$socketInfo->address.".".$socketInfo->port);
				$elaspe = $currentTime-$stateObject->$attrib;
				if($stateObject->$attrib!=null && $elaspe<=self::TIME_OUT)
				{
					$stateObject->$attrib = true;
				}
				else	
				{
					$stateObject->$attrib = false;
					$attrib = "SOCKET_".$socketInfo->port."_ELAPSE";
					$stateObject->$attrib = $elaspe;
				}
			}
		}
		
		
		$availableList = SocketHelper::GetSocketList();
		
		if(is_array($socketList))
		{
			$currentTime = time();
			$changed = false;
			foreach ($availableList as $key => $socketInfo) {
				$time = ServiceHelper::GetServiceTime("SOCKET.".$socketInfo->address.".".$socketInfo->port);
				$elaspe = $currentTime-$time;
				if($elaspe>self::TIME_OUT)
				{
					unset($availableList[$key]);
					$changed = true;
				}
			}
			if($changed)
				$availableList = array_filter($availableList);
		}
		
		
		return view('service.console',['prefix'=>Config::get('app.app_prefix'),'server_address'=>Config::get('app.server_address'),'services_info'=>$stateObject,'available_list'=>$availableList]);
	}
	
	function  anyManagement()
	{
		return view('service.management');
	}
	
}