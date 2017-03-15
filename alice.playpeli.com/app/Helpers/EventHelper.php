<?php
namespace App\Helpers;
use Redis;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\GiftController;
use App\Helpers\UtilsHelper;
use App\Models\UserModel;
use App\Models\GameModel;
use Illuminate\Support\Facades\Log;

class EventHelper
{
	public static function Lock($resource,$expire=5,$sleep=20000)
	{
		$cachekey = CmdHelper::CACHE_EVENT_PREFIX.".lock.".$resource;
		while(Redis::connection('event')->setnx($cachekey,microtime(true))!=1)
		{
			$timestamp1 = Redis::connection('event')->get($cachekey);
			if(microtime(true)-$timestamp1>$expire)		//过期
			{
				$timestamp2 = Redis::connection('event')->getset($cachekey,microtime(true));
				if($timestamp1==$timestamp2)	//如果检测时与设置时值相同,期间没有其他线程获取锁,所以成功获得锁
				{
					Log::info("EventHelper::Lock timeout,force get lock success key[$cachekey] resource[$resource] timestamp1[$timestamp1] timestamp2[$timestamp2]");
					break;
				}
				else 
				{
					Log::info("EventHelper::Lock timeout,force get lock failed key[$cachekey] resource[$resource] timestamp1[$timestamp1] timestamp2[$timestamp2]");
				}
				//Redis::connection('event')->expire($cachekey,$expire+1);
			}
			usleep($sleep);
		}
	}
	
	public static function Unlock($resource)
	{
		$cachekey = CmdHelper::CACHE_EVENT_PREFIX.".lock.".$resource;
		Redis::connection('event')->del($cachekey);
	}
	
}