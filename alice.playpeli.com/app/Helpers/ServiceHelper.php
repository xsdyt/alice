<?php
namespace App\Helpers;

use Redis;
use Illuminate\Support\Facades\Log;
use App\Models\UserModel;
use App\Helpers\CmdHelper;
use App\Helpers\UtilsHelper;

class ServiceHelper
{
	public static function Lock($resource,$expire=5,$sleep=10000)
	{
		$cachekey = CmdHelper::CACHE_CMD_PREFIX.".lock.".$resource;
		while(Redis::connection('service')->setnx($cachekey,microtime(true))!=1)
		{
			$timestamp1 = Redis::connection('service')->get($cachekey);
			if(microtime(true)-$timestamp1>$expire)		//过期
			{
				$timestamp2 = Redis::connection('service')->getset($cachekey,microtime(true));
				if($timestamp1==$timestamp2)	//如果检测时与设置时值相同,期间没有其他线程获取锁,所以成功获得锁
				{
					Log::info("ServiceHelper::Lock timeout,force get lock success key[$cachekey] resource[$resource] timestamp1[$timestamp1] timestamp2[$timestamp2]");
					break;
				}
				else 
				{
					Log::info("ServiceHelper::Lock timeout,force get lock failed key[$cachekey] resource[$resource] timestamp1[$timestamp1] timestamp2[$timestamp2]");
				}
				//Redis::connection('service')->expire($cachekey,$expire+1);
			}
			usleep($sleep);
		}
	}
	
	public static function Unlock($resource)
	{
		$cachekey = CmdHelper::CACHE_CMD_PREFIX.".lock.".$resource;
		Redis::connection('service')->del($cachekey);
	}
	
	public static function Reset()
	{
		Redis::connection('service')->flushdb();
	}
	
	public static function GetServiceTime($serviceName)
	{
		$cachekey = CmdHelper::CACHE_SERVICE_PREFIX.".TIME.".$serviceName;
		$time = Redis::connection('service')->get($cachekey);
		return $time;
	}
	
	public static function UpdateServiceTime($serviceName)
	{
		//Log::info("ServiceHelper::UpdateServiceTime $serviceName");
		$cachekey = CmdHelper::CACHE_SERVICE_PREFIX.".TIME.".$serviceName;
		Redis::connection('service')->set($cachekey,time());
	}

}