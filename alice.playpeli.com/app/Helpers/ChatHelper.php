<?php
namespace App\Helpers;
use Redis;
use Illuminate\Support\Facades\Log;
use App\Models\LogModel;

class ChatHelper
{
	public static function Lock($resource,$expire=5,$sleep=10000)
	{
		$cachekey = CmdHelper::CACHE_CHAT_PREFIX.".lock.".$resource;
		while(Redis::connection('chat')->setnx($cachekey,microtime(true))!=1)
		{
			$timestamp1 = Redis::connection('chat')->get($cachekey);
			if(microtime(true)-$timestamp1>$expire)		//过期
			{
				$timestamp2 = Redis::connection('chat')->getset($cachekey,microtime(true));
				if($timestamp1==$timestamp2)	//如果检测时与设置时值相同,期间没有其他线程获取锁,所以成功获得锁
				{
					Log::info("ChatHelper::Lock timeout,force get lock success key[$cachekey] resource[$resource] timestamp1[$timestamp1] timestamp2[$timestamp2]");
					break;
				}
				else 
				{
					Log::info("ChatHelper::Lock timeout,force get lock failed key[$cachekey] resource[$resource] timestamp1[$timestamp1] timestamp2[$timestamp2]");
				}
				//Redis::connection('chat')->expire($cachekey,$expire+1);
			}
			usleep($sleep);
		}
	}
	
	public static function Unlock($resource)
	{
		$cachekey = CmdHelper::CACHE_CHAT_PREFIX.".lock.".$resource;
		Redis::connection('chat')->del($cachekey);
	}
	
	public static function Say($roomId,$cid,$name,$content,$type=0,$source=1)
	{  
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
		$msg->name=$name;
		$msg->type=$type;
		$msg->content=$content;
		$msg->source=$source;
		$msg->time = microtime(true);
		array_push($array, $msg);
		
		Redis::multi();
		Redis::connection('chat')->set($cachekey,json_encode($array,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK));
		Redis::exec();
		
	}
	
}