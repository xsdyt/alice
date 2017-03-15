<?php
namespace App\Helpers;

use Redis;
use App\Helpers\CmdHelper;
use App\Models\CustomerModel;
use App\Models\CartModel;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use App\Models\LogModel;
use App\Models\WalletModel;
use App\Models\PokerRbModel;
use App\Models\LobbyModel;

class PokerRbHelper
{
	const ORIGIN_CARDS = Array(101,102,103,104,105,106,107,108,109,110,//111,112,113,
			201,202,203,204,205,206,207,208,209,210,//211,212,213,
			301,302,303,304,305,306,307,308,309,310,//311,312,313,
			401,402,403,404,405,406,407,408,409,410//,411,412,413
			);
	
	const COLOR_RED = 0;		//红
	const COLOR_BLACK = 1;		//黑
	const COLOR_ALICE = 2;		//爱丽丝
	
	const MOD_DEAL_MANUAL = 0;	//手动发牌
	const MOD_DEAL_AUTO = 1;	//自动发牌
	const MOD_DEAL_HALF = 2;	//半自动
	
	const TIME_WAIT_DEALING = 30;	//second
	const MAX_DEALING = 20;			//最大发牌数
	
	const ODDS = Array(0.9,0.9);	//赔率  Array(0.9,0.8);
	
	const COMMISSION_RED = 0.1;		// 宝石 兑换人民币 100:1
	const COMMISSION_BLACK = 0.1;	// 宝石 兑换人民币 100:1
	
	const TIME_LIMIT_BET = 25;
	const TIME_LIMIT_BUY = 25;

	const STATE_WAIT_BETTING = 1;	//等待下注
	const STATE_WAIT_CARD = 2;		//等待扫牌	
	const STATE_WAIT_BUY = 3;		//等待购买
	const STATE_SOLD_OUT = 4;		//售馨
	
	public static function Lock($resource,$expire=5,$sleep=20000)
	{
		$cachekey = CmdHelper::CACHE_POKER_RB_PREFIX.".lock.".$resource;
		while(Redis::connection('pokerrb')->setnx($cachekey,microtime(true))!=1)
		{
			$timestamp1 = Redis::connection('pokerrb')->get($cachekey);
				
			if(microtime(true)-$timestamp1>$expire)		//过期
			{
				$timestamp2 = Redis::connection('pokerrb')->getset($cachekey,microtime(true));
				if($timestamp1==$timestamp2)	//如果检测时与设置时值相同,期间没有其他线程获取锁,所以成功获得锁
				{
					Log::info("PokerRbHelper::Lock timeout,force get lock key[$cachekey] success resource[$resource] timestamp1[$timestamp1] timestamp2[$timestamp2]");
					break;
				}
				else 
				{
					Log::info("PokerRbHelper::Lock timeout,force get lock key[$cachekey] failed resource[$resource] timestamp1[$timestamp1] timestamp2[$timestamp2]");
				}
				//Redis::connection('pokerrb')->expire($cachekey,$expire+1);
			}
			usleep($sleep);
		}
		Redis::connection('pokerrb')->expire($cachekey,$expire+1);
	}
	
	public static function Unlock($resource)
	{
		$cachekey = CmdHelper::CACHE_POKER_RB_PREFIX.".lock.".$resource;
		Redis::connection('pokerrb')->del($cachekey);
	}
	
	
	public static function Reset()
	{
		$rooms = self::GetRooms();
		foreach ($rooms as $roomId){
			
			self::Lock("room.$roomId");
			$cachekey = CmdHelper::CACHE_POKER_RB_PREFIX.".room.".$roomId;
			Redis::connection('pokerrb')->set($cachekey,"");
			
			$cachekey = CmdHelper::CACHE_POKER_RB_PREFIX.".cards.".$roomId;
			Redis::connection('pokerrb')->set($cachekey,"");
				
			$cachekey = CmdHelper::CACHE_POKER_RB_PREFIX.".bettings.".$roomId;
			Redis::connection('pokerrb')->set($cachekey,"");
			self::Unlock("room.$roomId");
		}
	
		$cachekey = CmdHelper::CACHE_POKER_RB_PREFIX.".rooms";
		Redis::connection('pokerrb')->set($cachekey,"");
	}
	
	
	public static function Tick()
	{
		ServiceHelper::UpdateServiceTime("POKERRB.TICK");
		
		$startTime=microtime(true);
		//Log::info('tick start time '.$startTime);

		$rooms = self::GetRooms();
		foreach ($rooms as $roomId)
		{
			self::Lock("room.$roomId");
			$room = self::GetRoom($roomId);
			if($room)
			{
				if($room->timeLimit>0 && $room->timeLimit>time())
					$coolDown = $room->timeLimit-time();
				else
					$coolDown = 0;
				
				if($room->coolDown!=$coolDown)	
				{
					$room->coolDown=$coolDown;
					$room->version++;
					//Log::info("Tick room coolDown has changed,roomId[$room->roomId] coolDown[$coolDown]!");
				}
					
				if($room->mode==self::MOD_DEAL_AUTO)
				{
					self::Auto($room); //自动
				}
				else if($room->mode==self::MOD_DEAL_MANUAL)
				{
					self::Manual($room); //手动
				}
				else
				{
					self::Half($room); //半自动
				}
				self::SetRoom($roomId,$room);
			}
			self::Unlock("room.$roomId");
		}
		
		$endTime=microtime(true);
		$resultTime=$endTime-$startTime;
		if($resultTime>=3){
			Log::info('tick time start ['.$startTime.'[end '.$endTime.'] result['.$resultTime);
		}
		
	}

	public static function GetRooms()
	{
		$cachekey = CmdHelper::CACHE_POKER_RB_PREFIX.".rooms";
		$strRooms = Redis::connection('pokerrb')->get($cachekey);
		$rooms = json_decode($strRooms);
		if(!is_array($rooms))
			$rooms = Array();
		return $rooms;
	}
	
	public static function SetRooms($rooms)
	{
		$cachekey = CmdHelper::CACHE_POKER_RB_PREFIX.".rooms";
		$strRooms = json_encode($rooms,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
		Redis::connection('pokerrb')->set($cachekey,$strRooms);
	}
	
// 	public static function GetRoom(&$rooms,$roomId,$creation=true)
// 	{
// 		foreach ($rooms as $room){
// 			if($room->roomId == $roomId)
// 				return $room;
// 		}
	
// 		if($creation)
// 		{
// 			$room = new \stdClass();
// 			$room->roomId = $roomId;
// 			$room->balance = 0;
// 			$room->time = time();
// 			$room->mode = self::MOD_DEAL_AUTO;
// 			$room->round = 1;
// 			$room->state = 0;
// 			$room->lastdealtime=time();
// 			$room->version=1;
// 			$room->product = new \stdClass();
// 			$room->product->id = 1;
// 			$room->product->price = 2000;
// 			array_push($rooms,$room);
// 		}
// 		else
// 		{
// 			$room = null;
// 		}
// 		return $room;
// 	}

	public static function ExistRoom(&$rooms,$checkRoomId)
	{
		foreach ($rooms as $roomId){
			if($roomId == $checkRoomId)
				return true;
		}
		return false;
	}

	public static function GetRoom($roomId,$mode=self::MOD_DEAL_AUTO)
	{
		$cachekey = CmdHelper::CACHE_POKER_RB_PREFIX.".room.".$roomId;
		$strRoom = Redis::connection('pokerrb')->get($cachekey);
		$room = json_decode($strRoom);
		
		if(!is_object($room))
			$room=self::CreateRoom($roomId,$mode);

		return $room;
	}
	
	public static function CreateRoom($roomId,$mode)
	{
		$room = new \stdClass();
		$room->roomId = $roomId;
		$room->version=1;
		$room->balance = 0;
		$room->time = time();
		$room->mode = $mode;
		$room->round = 1;
		$room->state = self::STATE_SOLD_OUT;
		$room->pokerrb_id=0;
		$room->lastDealTime=time();
		$room->timeLimit = time()+self::TIME_LIMIT_BET;
		$room->coolDown = 0;
		$room->bets = 1;
		$room->fee = 0.1;
		$room->winners = Array();
		$room->prompt = "";
		$room->product = new \stdClass();
		$room->product->id = 0;
		//$room->product->name = 0;
		$room->product->price = 0;
		$room->product->gems = 0;
		$room->products = Array();

		Log::info("CreateRoom state has changed to STATE_SOLD_OUT,roomId[$room->roomId]!");
		
		self::Lock("rooms");
			
		$rooms = self::GetRooms();
			
		if(!self::ExistRoom($rooms,$roomId))
		{
			array_push($rooms,$roomId);
			self::SetRooms($rooms);
		}
			
		self::Unlock("rooms");
		return $room;
	}
	

	public static function SetRoom($roomId,$room)
	{
		$cachekey = CmdHelper::CACHE_POKER_RB_PREFIX.".room.".$roomId;
		$strRoom = json_encode($room,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
		Redis::connection('pokerrb')->set($cachekey,$strRoom);
	}
	
	//自动
	public static function Auto(&$room)
	{
		$elapse = $room->timeLimit-time();
		//Log::info("Auto room id[".$room->roomId."] state[".$room->state."] timeLimit[".$room->timeLimit."] - time[".time()."] = [".$elapse."]");
		$waitTime = self::TIME_WAIT_DEALING;
			
		if($room && $room->state != self::STATE_SOLD_OUT && time()-$room->time>$waitTime)
		{
			$cards = self::GetCards($room->roomId);
			
			if(is_array($cards->dealedCards) && count($cards->dealedCards)<self::MAX_DEALING)
			{
				$card = self::Deal($cards);
				$color = self::Color($card);
				$room->balance+=self::Settle($room,$card,$color);
				$room->round++;
				if($room->round%15=="0")//Minimum bet increase by ¥0.10 every 15 cards
				{
					$room->bets+=0.1;
				}
				$round=$room->round;
				switch ($round) {
					case $round>=1&&$round<=20:
						# code...
						$room->fee=Config::get('app.game_fees.f1');
						break;
					case $round>=21&&$round<=40:
					    $room->fee=Config::get('app.game_fees.f2');
					    break;
				    case $round>=41&&$round<=60:
				        $room->fee=Config::get('app.game_fees.f3');
				        break;
				    case $round>=61&&$round<=80:
				        $room->fee=Config::get('app.game_fees.f4');
				        break;
				    case $round>=81:
	                    $room->fee=Config::get('app.game_fees.f5');
				        break;
				}
			}
			else
			{
				self::Shuffle($cards);
				$room->round=1;
			}

			self::SetCards($room->roomId,$cards);
			
			if(($room->state==self::STATE_WAIT_BETTING||$room->state==self::STATE_WAIT_BUY )&& $room->timeLimit>0 && $room->timeLimit<time())
			{
				$room->timeLimit = 0;
				if($room->state==self::STATE_WAIT_BETTING)
				{
					$room->state = self::STATE_WAIT_CARD;
					Log::info("Auto state has changed to STATE_WAIT_CARD,roomId[$room->roomId]!");
				}
				else if($room->state==self::STATE_WAIT_BUY)
				{
					$room->state = self::STATE_WAIT_BETTING;
					$room->timeLimit = time()+self::TIME_LIMIT_BET;
					Log::info("Auto state has changed to STATE_WAIT_BETTING,roomId[$room->roomId]!");
				}
			}
			
			$room->version++;
			$room->time = time();
		}
	}
	
	//手动
	public static function Manual(&$room)
	{
		$elapse = $room->timeLimit-time();
		//Log::info("Manual room id[".$room->roomId."] state[".$room->state."] timeLimit[".$room->timeLimit."] - time[".time()."] = [".$elapse."]");
		
		if(($room->state==self::STATE_WAIT_BETTING||$room->state==self::STATE_WAIT_BUY )&& $room->timeLimit>0 && $room->timeLimit<time())
		{
			$room->timeLimit = 0;
			if($room->state==self::STATE_WAIT_BETTING)
			{
				$room->state = self::STATE_WAIT_CARD;
				Log::info("Manual state has changed to STATE_WAIT_CARD,roomId[$room->roomId]!");
			}
			else if($room->state==self::STATE_WAIT_BUY)
			{
				$room->state = self::STATE_WAIT_BETTING;
				$room->timeLimit = time()+self::TIME_LIMIT_BET;
				Log::info("Manual state has changed to STATE_WAIT_BETTING,roomId[$room->roomId]!");
			}
			$room->version++;
		}
	}

	//半自动
	public static function Half(&$room)
	{
		$elapse = $room->timeLimit-time();
		//Log::info("Half room id[".$room->roomId."] state[".$room->state."] timeLimit[".$room->timeLimit."] - time[".time()."] = [".$elapse."]");
		if(($room->state==self::STATE_WAIT_BETTING||$room->state==self::STATE_WAIT_BUY )&& $room->timeLimit>0 && $room->timeLimit<time())
		{
			$room->timeLimit = 0;
			if($room->state==self::STATE_WAIT_BETTING)
			{
				$room->state = self::STATE_WAIT_CARD;
				Log::info("Half state has changed to STATE_WAIT_CARD,roomId[$room->roomId]!");
			}
			else if($room->state==self::STATE_WAIT_BUY)
			{
				$room->state = self::STATE_WAIT_BETTING;
				$room->timeLimit = time()+self::TIME_LIMIT_BET;
				Log::info("Half state has changed to STATE_WAIT_BETTING,roomId[$room->roomId]!");
			}
			$room->version++;
		}
	}
	
	public static function GetCards($roomId)
	{
		$cachekey = CmdHelper::CACHE_POKER_RB_PREFIX.".cards.".$roomId;
		$strCards = Redis::connection('pokerrb')->get($cachekey);
		$cards = json_decode($strCards);
	
		if(!is_object($cards))
		{
			$cards = new \stdClass();
			self::Shuffle($cards);
		}
	
		return $cards;
	}
	
	public static function SetCards($roomId,$cards)
	{
		$cachekey = CmdHelper::CACHE_POKER_RB_PREFIX.".cards.".$roomId;
		$strCards = json_encode($cards,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
		Redis::connection('pokerrb')->set($cachekey,$strCards);
	}
	
	public static function GetBettings($roomId)
	{
		$cachekey = CmdHelper::CACHE_POKER_RB_PREFIX.".Bettings.".$roomId;
		$strBettings = Redis::connection('pokerrb')->get($cachekey);
		$bettings = json_decode($strBettings);
		if(!is_array($bettings))
			$bettings = Array();
	
		return $bettings;
	}
	
	public static function SetBettings($roomId,$bettings)
	{
		$cachekey = CmdHelper::CACHE_POKER_RB_PREFIX.".Bettings.".$roomId;
		$strCards = json_encode($bettings,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
		Redis::connection('pokerrb')->set($cachekey,$strCards);
	}
	
	public static function GetBetting(&$bettings,$cid,$creation=true)
	{
		if(is_array($bettings))
		{
			foreach ($bettings as $betting){
				if($betting->cid == $cid)
					return $betting;
			}
		}
	
		if($creation)
		{
			$betting = new \stdClass();
			$betting->cid = $cid;
			$betting->time = time();
			$betting->color = self::COLOR_RED;
			$betting->bet = 0;
			$betting->win = 0;
			array_push($bettings,$betting);
		}
		else
		{
			$betting = null;
		}
		return $betting;
	}

	public static function Color($card)
	{
		$value=0;
		$color=self::COLOR_RED;
		if($card>400)
		{
			$color=self::COLOR_RED;
			$value = $card%400;
		}
		else if($card>300)
		{
			$color=self::COLOR_BLACK;
			$value = $card%300;
		}
		else if($card>200)
		{
			$color=self::COLOR_RED;
			$value = $card%200;
		}
		else if($card>100)
		{
			$color=self::COLOR_BLACK;
			$value = $card%100;
		}
	
		if($value == 1 || $value==14)
			$color = self::COLOR_ALICE;
		
		return $color;
	}
	
	public static function Shuffle(&$cards)
	{
		$cards->dealedCards = Array();
		$cards->poolCards = self::ORIGIN_CARDS;
		shuffle($cards->poolCards);
	}
	
	public static function Finish(&$room)
	{
		self::Lock("bettings");
		
		$room->winners=Array();
		$bettings = self::GetBettings($room->roomId);
		
		if(is_array($bettings))
		{
			$changed = false;
			foreach($bettings as $key=>$betting)
			{
				if($betting->bet>0)
				{
					$result = CustomerModel::income($room->roomId,$room->round,$betting->cid,$betting->bet,LogModel::REASON_FINISH);
				}
			}
		}
		
		self::SetBettings($room->roomId,Array());
		
		self::Unlock("bettings");
	}
	
	
	public static function Settle(&$room,$card,$color)
	{
		$chips = 0;
		self::Lock("bettings");
		
		$room->winners=Array();
		$bettings = self::GetBettings($room->roomId);
	
		if(is_array($bettings))
		{
			$changed = false;
			foreach($bettings as $key=>$betting)
			{
				if($betting->bet>0)
				{
					$beforeBetting = $betting->bet;
	 				if($betting->color == $color)
	 				{
	 					$tmp = $betting->bet*self::ODDS[$color];
	 					$chips-=$tmp;
	 					$betting->bet+=$tmp;
	 					$betting->win++;
	 					
	 					if($betting->win>=3 && $betting->win%3==0)
	 					{
	 						$nickName = CustomerModel::getNickname($betting->cid);
	 						$winner = new \stdClass();
	 						$winner->nickName = $nickName;
	 						$winner->bet = $betting->bet;
	 						$winner->win = $betting->win;
	 						array_push($room->winners,$winner);
	 					}
	 					
	 					CmdHelper::AddWinEvent($betting->cid, $betting->win, $tmp);
	 				}
	 				else
	 				{
	 					$lose = $betting->bet;
	 					$chips+=$betting->bet;
	 					$betting->bet=0;
	 					$betting->win=0;
	 					unset($bettings[$key]);
	 					$changed = true;
	 					CmdHelper::AddLoseEvent($betting->cid, $lose);
	 				}
	 				
	 				LogModel::createSettlementLog($room->roomId,$room->product->id,$room->round,$room->mode,$betting->cid,$betting->win,$betting->color,$beforeBetting,$betting->bet,$card,$color);
				}
			}
			if($changed)
				$bettings = array_values($bettings);
		}
	
		self::SetBettings($room->roomId,$bettings);
	
		self::Unlock("bettings");
		
		//如果有连赢3局的玩家，则进入等待购买状态
		if(count($room->winners)>0)
		{
			$room->timeLimit = time()+self::TIME_LIMIT_BUY;
			$room->state = self::STATE_WAIT_BUY;
			Log::info("Settle state has changed to STATE_WAIT_BUY,roomId[$room->roomId]!");
		}
		else
		{
			$room->timeLimit = time()+self::TIME_LIMIT_BET;
			$room->state = self::STATE_WAIT_BETTING;
			Log::info("Settle state has changed to STATE_WAIT_BETTING,roomId[$room->roomId]!");
		}

		return $chips;
	}
	
	public static function Deal($cards,$card=0)
	{
		if($card==0)
			$card = array_shift($cards->poolCards);
		array_push($cards->dealedCards,$card);
		return $card;
	}
	
	public static function TickRoom($roomId,$cid)
	{
		if($roomId>0)
		{
			$room = self::GetRoom($roomId);
			if($room)
			{
				$cachekey = CmdHelper::CACHE_POKER_RB_PREFIX.".room.version.".$cid;
				$version = Redis::connection('pokerrb')->get($cachekey);
				if($version==null || $version=="")
					$version = 1;
	
				if($room->version!=$version)
				{
					$version = $room->version;
					Redis::connection('pokerrb')->set($cachekey,$version);
					$room->result = 1;
					
					$room->lastTick = $room->last_tick = microtime(true);
					$cards = self::GetCards($room->roomId);
					if($cards)
						$room->cards = $cards->dealedCards;
					
					$bettings = self::GetBettings($room->roomId);
					if($bettings)
					{
						$betting = self::GetBetting($bettings,$cid,false);
						if($betting)
						{
							$room->betting = $betting;
							$room->betting->balance = WalletModel::balance($cid);
						}
					}
					
					if(!isset($room->betting))
					{
						$room->betting = new \stdClass();
						$room->betting->cid = $cid;
						$room->betting->time = 0;
						$room->betting->color = 0;
						$room->betting->win = 0;
						$room->betting->bet = 0;
						$room->betting->balance = WalletModel::balance($cid);
					}
					return $room;
				}
			}
		}
		return null;
	}
	
	public static function Create($roomId,$mode)
	{
		$room = self::CreateRoom($roomId,$mode);
		self::SetRoom($roomId, $room);
	}
	
	public static function Enter($roomId,$mode,$cid)
	{
		$cachekey = CmdHelper::CACHE_POKER_RB_PREFIX.".room.version.".$cid;
		$version = Redis::connection('pokerrb')->set($cachekey,0);

		$room = self::GetRoom($roomId,$mode);
		self::SetRoom($roomId, $room);
	}
	
	public static function Bet(&$room,$cid,$color,$bet)
	{
		if($color==self::COLOR_RED)
			$commission = self::COMMISSION_RED;
		else if($color==self::COLOR_BLACK)
			$commission = self::COMMISSION_BLACK;
		else
			$commission = 0;
		
		$customers = CustomerModel::getCustomers($cid);
		$balance = WalletModel::balance($cid);
		if($customers && count($customers)>0)
		{	
			$customer = $customers[0];
			$customer->gems = $balance;
			self::Lock("bettings");
			
			$bettings = self::GetBettings($room->roomId);
			$betting = self::GetBetting($bettings,$cid);
			$betrmb=$bet/100;
			switch ($betrmb) {
				case $betrmb>=1&&$betrmb<=20:
					# code...
					$fee=Config::get('app.game_fees.f1');
					break;
				case $betrmb>=21&&$betrmb<=40:
				    $fee=Config::get('app.game_fees.f2');
				    break;
			    case $betrmb>=41&&$betrmb<=60:
			        $fee=Config::get('app.game_fees.f3');
			        break;
			    case $betrmb>=61&&$betrmb<=80:
			        $fee=Config::get('app.game_fees.f4');
			        break;
			    case $betrmb>=81:
                    $fee=Config::get('app.game_fees.f5');
			        break;
			    default:
			        $fee=0;
			        break;
			}
			// $commission = $bet*$room->fee;
			$commission =$fee*100;
			Log::info('bet commission='.$commission.']cid='.$cid);
			if($betting->bet==0 && $bet>0)
			{
				if($balance>=$commission+$bet)
				{
					CustomerModel::expense($room->roomId,$room->round,$cid,$commission,LogModel::REASON_COMMISSION);
					$result = CustomerModel::expense($room->roomId,$room->round,$cid,$bet,LogModel::REASON_BET);	
					
					$result->commission = $commission;
					if($result->result==1)
					{
						if($room->product->price>$commission)
							$room->product->price-=$commission;
						else
							$room->product->price=0;
				
						if($room->product->gems>$commission)
							$room->product->gems-=$commission;
						else
							$room->product->gems=0;
											
						foreach ($room->products as $product)
						{
							if($product->price>$commission)
								$product->price-=$commission;
							else
								$product->price=0;
							
							if($product->gems>$commission)
								$product->gems-=$commission;
							else
								$product->gems=0;
						}
							
						$room->version++;
						self::SetRoom($room->roomId, $room);
						$betting->time = time();
						$betting->color = $color;
						$betting->bet+=$bet;
						self::SetBettings($room->roomId,$bettings);
	
						LogModel::createBettingLog($room->roomId, $room->round, $cid, $betting->color, $betting->bet, $commission);
						$result->fee=$fee;
					}
				}
				else 
				{
					$result = new \stdClass();
					$result->result = 2;//金额不足
					$result->balance = $balance;
				}
			}
			else
			{
				$room->version++;
				self::SetRoom($room->roomId, $room);
				$result = new \stdClass();
				$result->result = 1;
				$result->balance = $balance;
				$betting->time = time();
				$betting->color = $color;
				self::SetBettings($room->roomId,$bettings);
			}
			
			self::Unlock("bettings");
		}

		if(!isset($result))
			Log::info("Bet expense failed. result is null,roomId[$room->roomId] customId[$cid]!");
		else if($result->result==0)
			Log::info("Bet expense failed. roomId[$room->roomId] customId[$cid] result[$result->result] balance[$result->balance]!");
		
		return $result;
	}
	
	public static function CashOut($room,$cid)
	{
		$chips = 0;
		self::Lock("bettings");
	
		$bettings = self::GetBettings($room->roomId);
		if(is_array($bettings))
		{
			foreach($bettings as $betting)
			{
				if($betting->cid==$cid)
				{
					$chips = $betting->bet;
					$betting->bet = 0;
					$betting->win = 0;
				}
			}
		}
		self::SetBettings($room->roomId,$bettings);
		self::Unlock("bettings");
		
		$result = CustomerModel::income($room->roomId,$room->round,$cid,$chips,LogModel::REASON_GAIN);
		
		if(!isset($result))
			Log::info("CashOut income failed. result is null,roomId[$room->roomId] customId[$cid]!");
		else if($result->result==0)
			Log::info("CashOut income failed. roomId[$room->roomId] customId[$cid] result[$result->result] balance[$result->balance]!");
		
		return $result;
	}
	
	//后台房间开始方法
	public static function StartPokerRb($roomId,$pokerRb)
	{
		if($roomId>0 && $pokerRb && $pokerRb->countdown>0)
		{
			self::Lock("room.$roomId");
			$room = self::GetRoom($roomId);
			// if($room->state==self::STATE_SOLD_OUT||$room->state==self::STATE_WAIT_BETTING)
			// {
				if($room)
				{
					$room->balance = 0;
					$room->time = time();
					$room->round = 1;
					$room->state = self::STATE_WAIT_BETTING;
					$room->lastDealTime=time();
					$room->timeLimit = time()+self::TIME_LIMIT_BET;
					$room->coolDown = 0;
					$room->bets = 1;
		            $room->fee = 0.2;
					$room->winners = Array();
					$room->pokerrb_id = $pokerRb->id;
					$room->prompt = "";
					$room->product = new \stdClass();
					$room->product->id = $pokerRb->product_id;
					$room->product->price = $pokerRb->product_price;
					$room->product->gems = $pokerRb->product_price;
					
					$room->products = Array();
					$products = explode('|',$pokerRb->products);
					foreach($products as $strProduct)
					{
						$productInfo = explode(':',$strProduct);
						
						if(count($productInfo)>=2)
						{
							$product = new \stdClass();
							$product->id = $productInfo[0];
							$product->price = $productInfo[1];
							$product->gems = $productInfo[1];
							array_push($room->products, $product);
						}
					}
					
					$room->version++;
					self::SetRoom($roomId, $room);
					Log::info("StartPokerRb state has changed to STATE_WAIT_BETTING,roomId[$room->roomId]!");
				}
				self::Unlock("room.$roomId");
			// }
		}
	}
		
	//结束方法
	public static function StopPokerRb($roomId)
	{	
		if($roomId>0)
		{
			self::Lock("room.$roomId");
			$room = self::GetRoom($roomId);

			if($room)
			{
				$room->prompt = "";
				$room->state = self::STATE_SOLD_OUT;
				$room->timeLimit = 0;
				$room->version++;;
				self::SetRoom($roomId, $room);
				Log::info("StopPokerRb state has changed to STATE_SOLD_OUT,roomId[$room->roomId]!");
			}
			self::Unlock("room.$roomId");
		}
	}

	public static function AddToCart($roomId,$cid,$productId)
	{
		$result = 0;
		if($roomId>0)
		{
			self::Lock("room.$roomId");
			$room = self::GetRoom($roomId);
			if($room && $room->state != self::STATE_SOLD_OUT)
			{
				$chips = 0;
				self::Lock("bettings");
				$bettings = self::GetBettings($roomId);
				if(is_array($bettings))
				{
					foreach($bettings as $betting)
					{
						if($betting->cid==$cid)
						{
							$chips = $betting->bet;
							$betting->bet = 0;
							$betting->win = 0;
							break;
						}
					}
				}
				self::SetBettings($roomId,$bettings);
				self::Unlock("bettings");
				
				if($productId>0)
				{
					foreach ($room->products as $product)
					{
						if($product->id==$productId)
						{
							CartModel::clearCart($cid);
							CartModel::addProductToCart($cid, $product->id, $product->gems,$chips,1);
							LogModel::createCartLog($cid, $product->id, $product->gems,$chips,1);
						}
					}
				}
				else 
				{
					CartModel::clearCart($cid);
					CartModel::addProductToCart($cid, $room->product->id, $room->product->gems,$chips,1);
					LogModel::createCartLog($cid, $room->product->id, $room->product->gems,$chips,1);
				}
				
				$nickName = CustomerModel::getNickname($cid);
				$room->prompt = "恭喜[$nickName]成功购买了商品!";
				$room->state = self::STATE_SOLD_OUT;
				$room->timeLimit = 0;
				$room->version++;
				$result=1;
				self::SetRoom($roomId, $room);
				PokerRbModel::PokerRbDisabled($room->pokerrb_id);
				Log::info("AddToCart state has changed to STATE_SOLD_OUT,roomId[$room->roomId]!");
			}
			self::Unlock("room.$roomId");
		}
		return $result;
	}

 //    //红与黑自动执行
	// public static function AutoPokerRb()
	// {
	//    $scheduleInfo=self::getSchedule();
	//    // echo "<pre>";
	//    // print_r($scheduleInfo);
	//    foreach ($scheduleInfo as $key => $value) 
	//    {
	// 	    $room = self::GetRoom(10);
	// 	    if($room->state==self::STATE_SOLD_OUT)
	// 	    {
	// 	    	$goods=explode(',',$value->activitys_id);
	// 	    	for($i=0;$i<count($goods);$i++)
	// 	    	{
 //                    $pokerRb=PokerRbModel::getPokerRb($goods[$i]);
                     
 //                    if(!$pokerRb[0]->enabled)
 //                    {
 //                    	// print_r($pokerRb);exit;
 //                        self::pokerRbEnabled($goods[$i]);
 //                        break;
 //                    }
	// 	    	}         
	// 	    }
	//    	}
	// }
    //红与黑自动执行
	public static function AutoPokerRb()
	{
	   $scheduleInfo=self::getSchedule();
	   //Log::info('AutoPokerRb [=]'.json_encode($scheduleInfo));
	   // // echo "<pre>";
	   // // print_r($scheduleInfo);
	   if(count($scheduleInfo)>0)
	   {
           foreach ($scheduleInfo as $key => $value)
		   {
		   	    if($value->is_line)
		   	    {
				    $room = PokerRbHelper::GetRoom($value->room_id);
				    if($room->state==PokerRbHelper::STATE_SOLD_OUT)
				    {
				    	$goods=explode(',',$value->activitys_id);
				    	for($i=0;$i<count($goods);$i++)
				    	{
		                    $pokerRb=PokerRbModel::getPokerRb($goods[$i]);
		                    if(count($pokerRb)>0)
		                    {
                                if(!$pokerRb[0]->enabled&&$pokerRb[0]->end_time!='0000-00-00 00:00:00')
			                    {
			                    	//Log::info('AutoPokerRb start pokerid='.$goods[$i]);
			                    	// print_r($pokerRb);exit;
			                        self::pokerRbEnabled($goods[$i]);
			                        break;
			                    }
		                    }
		                 }   
				    }
			   	}
			}
	    }
	}

	//开始
	public static function pokerRbEnabled($id,$duration=3600)
	{
		$pokerrb=PokerRbModel::PokerRbEnabled($id,$duration);
	    if($pokerrb && count($pokerrb)>0)
	    {
	        $poker = $pokerrb[0];
	        self::StartPokerRb($poker->room_id, $poker);
	    }
	
	    return json_encode($poker);

	}
    //关闭
    public static function pokerRbDisabled($id)
    {
      $pokerrb=PokerRbModel::PokerRbDisabled($id);
      
      if($pokerrb && count($pokerrb)>0)
      {
		$poker = $pokerrb[0];
		self::StopPokerRb($poker->room_id);
      }      
      return json_encode($poker);
    }

    //获取所有红与黑房间产品
	public static function getSchedule()
	{
		return LobbyModel::getSchedule(2);
	}
}