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
use App\Models\CustomerModel;
use App\Models\LogModel;

class ChatController extends Controller
{
	function anySay(){
        $source=Input::get('source','1'); 
        $roomId = Input::get('roomid','1');
        $cid = Input::get('cid','0');
        $deviceType = Input::get('device_type','0');
        //$name = Input::get('name','匿名');
		$type = Input::get('type','0');
        $content = Input::get('content','聊天记录');
        
        $cmd = "#### set nickname ";
        if(strpos($content, $cmd) === 0)
        {
			$length = strlen($content)-strlen($cmd);
			$nickname = substr($content, -$length);
			CustomerModel::setNickname($cid, $nickname);
			$result = UtilsHelper::createResult(1, 0);
			$result->cid = $cid;
			$content = json_encode($result,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
			$response = Response::make($content, 200);
			$response->header('Content-Type','text/html');
			return $response;
        }
        
        if($deviceType=="1"){
            $content = base64_decode(str_replace(" ", "+", $content));
        }
        $customer=CustomerModel::getCustomers($cid);	

        $cachekey = CmdHelper::CACHE_CHAT_PREFIX.".record.version.$roomId";
        $version = Redis::connection('chat')->get($cachekey);
        if($version==null || $version=="")
           $version = 1;
        $version++;
        Redis::connection('chat')->set($cachekey,$version);
		
		$cachekey = CmdHelper::CACHE_CHAT_PREFIX.".record.$roomId";
		
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
		$msg->customerid = $cid;
		if(count($customer)>0)
			$msg->name=$customer[0]->nickname;
		else
			$msg->name = "未知";
		$msg->type=$type;
		$msg->content=$content;
        $msg->source=$source;
		$msg->time = microtime(true);
		LogModel::createChatLog($cid, $content, $roomId);
		array_push($array, $msg);
		
		Redis::multi();				
		Redis::connection('chat')->set($cachekey,json_encode($array,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK));
		Redis::exec();	

		$result = new \stdClass();
		$result->result=1;
		$result->cid = $cid;
		$content = json_encode($result,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
		$response = Response::make($content, 200);
		$response->header('Content-Type','text/html');
		return $response;
	}
	
	function anyRecord()
	{
		$roomId = Input::get('roomid','1');
		$time = Input::get('time',microtime(true)-300);
		$cachekey = CmdHelper::CACHE_CHAT_PREFIX.".record.$roomId";
		
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
		$cachekey = CmdHelper::CACHE_CHAT_PREFIX.".record.$roomId";
		Redis::connection('chat')->set($cachekey,"");
		$content = "Cleared";
		$response = Response::make($content, 200);
		$response->header('Content-Type','text/html');
		return $response;	
	}
	
}