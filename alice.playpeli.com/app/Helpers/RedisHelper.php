<?php
namespace App\Helpers;

use Redis;
use App\Models\UserModel;
use App\Helpers\CmdHelper;
use App\Helpers\UtilsHelper;

class RedisHelper
{
	const DB_DEFAULT	=	10;	//默认数据库
	const DB_GAME		=	11;		//聊天，送礼，打赏
	const DB_USER		=	12;		//玩家游戏信息
	const DB_DEALER		=	13;		//荷官客户端信息
	
	public static function Lock($resource,$expire=30,$sleep=10000)
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
					Log::info("RedisHelper::Lock timeout,force get lock success key[$cachekey] resource[$resource] timestamp1[$timestamp1] timestamp2[$timestamp2]");
					break;
				}
				else 
				{
					Log::info("RedisHelper::Lock timeout,force get lock failed key[$cachekey] resource[$resource] timestamp1[$timestamp1] timestamp2[$timestamp2]");
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
}