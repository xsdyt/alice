<?php
namespace App\Helpers;
use Redis;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\GiftController;
use App\Helpers\UtilsHelper;
use App\Helpers\EventHelper;
use App\Models\UserModel;
use App\Models\GameModel;
use Illuminate\Support\Facades\Log;

class CmdHelper
{
	const CACHE_RESOURCE_PREFIX = "playpeli.resource.url";
	const CACHE_EVENT_PREFIX = "playpeli.alice.event";
	const CACHE_CMD_PREFIX = "playpeli.alice.cmd";
	const CACHE_CHAT_PREFIX = "playpeli.alice.chat";
	const CACHE_GIFT_PREFIX = "playpeli.alice.gift";
	const CACHE_AWARD_PREFIX = "playpeli.alice.award";
	const CACHE_AUCTION_PREFIX = "playpeli.alice.auction";
	const CACHE_POKER_RB_PREFIX = "playpeli.alice.pokerrb";
    const CACHE_LOGIN_GAME = "playpeli.alice.login";
    const CACHE_POKER_RB_BET_PREFIX = "playpeli.alice.war.bet";
    const CACHE_ROOM_PREFIX = "playpeli.alice.room";
    const CACHE_SERVICE_PREFIX = "playpeli.alice.service";
    const CACHE_SOCKET_PREFIX = "playpeli.alice.socket";
    const CMD_EVENT = 1;			//事件
	const CMD_CHAT = 11;			//聊天
	const CMD_GIFT = 12;			//送礼
	const CMD_AUCTION = 21;
	const CMD_POKER_RB = 22;		//Poker Red & Black
	
	const EVENT_POKER_RB_WIN = 11;
	const EVENT_POKER_RB_LOSE = 12;
	
	const CHECK_TIME_OUT = 50;
	
	public static function Lock($resource,$expire=5,$sleep=10000)
	{
		$cachekey = CmdHelper::CACHE_CMD_PREFIX.".lock.".$resource;
		//Log::info("CmdHelper::Lock key[$cachekey] resource[$resource]");
		while(Redis::connection('pokerrb')->setnx($cachekey,microtime(true))!=1)
		{
			$timestamp1 = Redis::connection('pokerrb')->get($cachekey);
			if(microtime(true)-$timestamp1>$expire)		//过期
			{
				$timestamp2 = Redis::connection('pokerrb')->getset($cachekey,microtime(true));
				if($timestamp1==$timestamp2)	//如果检测时与设置时值相同,期间没有其他线程获取锁,所以成功获得锁
				{
					Log::info("CmdHelper::Lock timeout,force get lock success key[$cachekey] resource[$resource] timestamp1[$timestamp1] timestamp2[$timestamp2]");
					break;
				}
				else 
				{
					Log::info("CmdHelper::Lock timeout,force get lock failed key[$cachekey] resource[$resource] timestamp1[$timestamp1] timestamp2[$timestamp2]");
				}
				//Redis::connection('pokerrb')->expire($cachekey,$expire+1);
			}
			usleep($sleep);
		}
	}
	
	public static function Unlock($resource)
	{
		$cachekey = CmdHelper::CACHE_CMD_PREFIX.".lock.".$resource;
		Redis::connection('pokerrb')->del($cachekey);
		//Log::info("CmdHelper::Unlock key[$cachekey] resource[$resource]");
	}
	
	public static function AddWinEvent($cid,$win,$income)
	{
		$event = new \stdClass();
		$event->id = self::EVENT_POKER_RB_WIN;
		$event->win = $win;
		$event->income = $income;
		self::AddEvent($cid,$event);
	}
	
	public static function AddLoseEvent($cid,$lose)
	{
		$event = new \stdClass();
		$event->id = self::EVENT_POKER_RB_LOSE;
		$event->lose = $lose;
		self::AddEvent($cid,$event);
	}
	
	public static function AddEvent($cid,$event)
	{
		CmdHelper::Lock("event.$cid");
		$startTime = UtilsHelper::getMillisecond();
		$cachekey = CmdHelper::CACHE_EVENT_PREFIX.".events.$cid";
		$events = json_decode(Redis::connection('pokerrb')->get($cachekey));
		if(is_object($events))
			$events = (array)$events;
		
		if(!is_array($events))
			$events = array();
		
		array_push($events,$event);
		$strEvents = json_encode($events,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
		Redis::connection('pokerrb')->set($cachekey,$strEvents);
		
		$elapse = intval(UtilsHelper::getMillisecond()-$startTime);
		if($elapse>self::CHECK_TIME_OUT)
			Log::info("CmdHelper::AddEvent Execute Timeout cid[$cid] elapse[$elapse]");
		
		CmdHelper::Unlock("event.$cid");
	}
	
	public static function CheckEventCmd(&$cmds,$cid)
	{
		CmdHelper::Lock("event.$cid");
		$startTime = UtilsHelper::getMillisecond();
		$cachekey = CmdHelper::CACHE_EVENT_PREFIX.".events.$cid";
		$array = json_decode(Redis::connection('pokerrb')->get($cachekey));
		if(is_object($array))
			$array = (array)$array;
		
		if(!is_array($array))
			$array = array();
		
		$size = count($array);
		if($size>0)
		{
			$cmd = new \stdClass();
			$cmd->id = self::CMD_EVENT;
			$cmd->content = $array;
			array_push($cmds,$cmd);
		}
		Redis::connection('pokerrb')->set($cachekey,"");
		
		$elapse = intval(UtilsHelper::getMillisecond()-$startTime);
		if($elapse>self::CHECK_TIME_OUT)
			Log::info("CmdHelper::CheckEventCmd Execute Timeout cid[$cid] elapse[$elapse]");
		
		CmdHelper::Unlock("event.$cid");
	}
	
	public static function CheckChatCmd(&$cmds,$roomId,$cid)
	{	
		$cachekey = CmdHelper::CACHE_CHAT_PREFIX.".record.version.$roomId.$cid";
		$version = Redis::connection('chat')->get($cachekey);
                
		if($version==null || $version=="")
			$version = 0;
		$currentVersion = $version;
		
		$cachekey = self::CACHE_CHAT_PREFIX.".record.$roomId";

		$array = json_decode(Redis::connection('chat')->get($cachekey));
		if(is_object($array))
			$array = (array)$array;
		
		if(!is_array($array))
			$array = array();
		
		$size = count($array);	
		//Log::info("CheckChatCmd time[$startTime - $endTime] roomid[$roomId] size[$size]");
		
		$result_array = array();
		foreach ($array as $record)
		{
			if(isset($record->version) && $record->version>$version)
			{
				if($record->version>$currentVersion)
					$currentVersion = $record->version;
				          
                                //echo $currentVersion;
				array_push($result_array, $record);
				//Log::info("CheckChatCmd Hit [$record->time] $record->name:$record->content");
			}
			else 
			{
				//Log::info("CheckChatCmd Miss [$record->time] $record->name:$record->content");
			}
		}
                
               //print_r($result_array);
		
		if($currentVersion>$version)
		{
			$cachekey = CmdHelper::CACHE_CHAT_PREFIX.".record.version.$roomId.$cid";
			Redis::connection('chat')->set($cachekey,$currentVersion);
		}
		
		//Log::info("CheckChatCmd -------------------------------------------------");

		$size = count($result_array);
		if($size>0)
		{
			$cmd = new \stdClass();
			$cmd->id = self::CMD_CHAT;
			$cmd->content = $result_array;
			array_push($cmds,$cmd);
		}
	}
	
	public static function CheckGiftCmd(& $cmds,$roomId,$cid)
	{
		$cachekey = CmdHelper::CACHE_GIFT_PREFIX.".record.version.$roomId.$cid";
		$version = Redis::connection('chat')->get($cachekey);
	
		if($version==null || $version=="")
			$version = 0;
		$currentVersion = $version;
		$cachekey = self::CACHE_GIFT_PREFIX.".record.$roomId";
		$array = json_decode(Redis::connection('chat')->get($cachekey));
		
		if(is_object($array))
			$array = (array)$array;
	
		if(!is_array($array))
			$array = array();
	
		$size = count($array);

	
		$result_array = array();
		foreach ($array as $record)
		{
			if(isset($record->version) && $record->version>$version)
			{
				if($record->version>$currentVersion)
					$currentVersion = $record->version;
	
								//echo $currentVersion;
				array_push($result_array, $record);
								//Log::info("CheckGiftCmd Hit [$record->time] $record->name:$record->content");
			}
			else
			{
							//Log::info("CheckGiftCmd Miss [$record->time] $record->name:$record->content");
			}
		}
	
		if($currentVersion>$version)
		{
			$cachekey = CmdHelper::CACHE_GIFT_PREFIX.".record.version.$roomId.$cid";
			Redis::connection('chat')->set($cachekey,$currentVersion);
		}
	
		$size = count($result_array);
		if($size>0)
		{
			$cmd = new \stdClass();
			$cmd->id = self::CMD_GIFT;
			$cmd->content = $result_array;
			array_push($cmds,$cmd);
		}
	}
	
	public static function CheckAuctionCmd(&$cmds,$roomId,$cid)
	{
		$cachekey = CmdHelper::CACHE_AUCTION_PREFIX.".game.version.$roomId.$cid";
		$version = Redis::connection('auction')->get($cachekey);
	
		if($version==null || $version=="")
			$version = 0;
		$currentVersion = $version;
		$room = AuctionHelper::TickRoom($roomId, $cid);
		if($room)
		{
			$cmd = new \stdClass();
			$cmd->id = self::CMD_AUCTION;
			$cmd->content = $room;
			array_push($cmds,$cmd);
		}

		if($currentVersion>$version)
		{
			$cachekey = CmdHelper::CACHE_AUCTION_PREFIX.".game.version.$roomId.$cid";
			Redis::connection('auction')->set($cachekey,$currentVersion);
		}
	}
	
	public static function CheckPokderRbCmd(&$cmds,$roomId,$cid)
	{
		$cachekey = CmdHelper::CACHE_POKER_RB_PREFIX.".game.version.$roomId.$cid";
		$version = Redis::connection('pokerrb')->get($cachekey);
		
		if($version==null || $version=="")
			$version = 0;
		$currentVersion = $version;
		$room = PokerRbHelper::TickRoom($roomId, $cid);
		if($room)
		{
			$cmd = new \stdClass();
			$cmd->id = self::CMD_POKER_RB;
			$cmd->content = $room;
			array_push($cmds,$cmd);
		}
		
		if($currentVersion>$version)
		{
			$cachekey = CmdHelper::CACHE_POKER_RB_PREFIX.".game.version.$roomId.$cid";
			Redis::connection('pokerrb')->set($cachekey,$currentVersion);
		}
	}
	
}