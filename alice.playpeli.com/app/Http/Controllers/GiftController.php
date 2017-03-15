<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Redis;

use App\Helpers\UtilsHelper;
use App\Helpers\CmdHelper;
use App\Helpers\RedisHelper;
use App\Helpers\GiftHelper;
use App\Models\CustomerModel;
use App\Models\WalletModel;

class GiftController extends Controller
{
	function anySend(){
        $roomId = Input::get('roomid','1');
        $fid = Input::get('fid','0');
        $tid = Input::get('tid','0');
        $gift = Input::get('gift','0');
        $num = Input::get('num','1');
        //$name = Input::get('name','匿名');
        $content = Input::get('content','送礼物');
        $giftConfig=Config::get('app.gift');
        $from=CustomerModel::getCustomers($fid);	
        $to=CustomerModel::getCustomers($tid);
        if(!isset($giftConfig[$gift]))
        {
            $res['result']=0;	
        }else
        {
        	$money=$giftConfig[$gift];
	        $money=$money*$num*100;
	        $res=GiftHelper::sendGift($fid,$gift,$num,$money,$roomId);
        }
        $result = new \stdClass();
	    $result->result=0;//0失败1成功
		if($res['result']==1)
		{
            $fromName = "";
	        $toName = "";
	        
	        if(count($from)>0)
	        	$fromName = $from[0]->nickname;
	        if(count($to)>0)
				$toName = $to[0]->nickname;
	        
	        $cachekey = CmdHelper::CACHE_GIFT_PREFIX.".record.version.$roomId";
	        $version = Redis::connection('chat')->get($cachekey);
	        if($version==null || $version=="")
	           $version = 1;
	        $version++;
	        Redis::connection('chat')->set($cachekey,$version);
			
			$cachekey = CmdHelper::CACHE_GIFT_PREFIX.".record.$roomId";
			
			Redis::watch($cachekey);					// watch
			$array=null;
			
			$record = Redis::connection('chat')->get($cachekey);	
			if($record!=null && $record!="")
				$array = json_decode($record);
			
			if(is_object($array))
				$array = (array)$array;
			
			if(!is_array($array))
				$array = array();
			
			$time = microtime(true)-300;

			$changed = false;
			//print_r($array);
			foreach ($array as $key => $value) {
				if($value->time<$time)
				{
			    	unset($array[$key]);
			    	$changed = true;
				}
			}

			if($changed)
				$array = array_values($array);

			$msg = new \stdClass();
			$msg->version = $version;
			$msg->fid = $fid;
			$msg->tid = $res['data']->dealer_id;		
			$msg->fromName=$fromName;
			$msg->toName=$toName;		
			$msg->gift=$gift;
			$msg->num = $num;
			$msg->content=$content;
			$msg->time = microtime(true);
			array_push($array, $msg);
			
			Redis::multi();				
			Redis::connection('chat')->set($cachekey,json_encode($array,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK));
			Redis::exec();	
            $balance=WalletModel::balance($fid);
			$result = new \stdClass();
			$result->result=1;
			$result->fid = $fid;
			$result->tid = $res['data']->dealer_id;
			$result->balance=$balance;
			$result->fromName = $fromName;
		}
        $result->result=$res['result'];
		$content = json_encode($result,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
		$response = Response::make($content, 200);
		$response->header('Content-Type','text/html');
		return $response;
	}
	
	function anyRecord()
	{
		$roomId = Input::get('roomid','1');
		$time = Input::get('time',microtime(true)-300);
		$cachekey = CmdHelper::CACHE_GIFT_PREFIX.".record.$roomId";
		
		$array = json_decode(Redis::connection('chat')->get($cachekey));
		
		if(is_object($array))
			$array = (array)$array;
		
		if(!is_array($array))
			$array = array();
		
		$result_array = array();
		foreach ($array as $record)
		{
			if($record->time>=$time)
				array_push($result_array, $record);
		}
		
		$content = json_encode($result_array,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
		$response = Response::make($content, 200);
		$response->header('Content-Type','text/html');
		return $response;		
	}
	
	function anyClear()
	{
		$roomId = Input::get('roomid','1');
		$cachekey = CmdHelper::CACHE_GIFT_PREFIX.".record.$roomId";
		Redis::connection('chat')->set($cachekey,"");
		$content = "Cleared";
		$response = Response::make($content, 200);
		$response->header('Content-Type','text/html');
		return $response;	
	}
}