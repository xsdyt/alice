<?php
namespace App\Helpers;

use Redis;
use Swoole;
use Illuminate\Support\Facades\Log;
use App\Helpers\CmdHelper;
use App\Helpers\UtilsHelper;
use App\Helpers\ServiceHelper;

class SwooleHelper
{
	public static $CMD_ONCONNECTED = 1;
	public static $CMD_CUSTOMER_ENTER = 2;
	public static $CMD_ACTION_CMDS = 3;
	
	public static $CHECK_EXIST_INTERVAL = 1;
	public static $CHECK_TIME_OUT = 50;
	
	
	public static $headLen = 4;
// 	public static $recvBufs = array();
	public static $customers = array();
	public static $clients = array();
	
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
					Log::info("SwooleHelper::Lock timeout,force get lock success key[$cachekey] resource[$resource] timestamp1[$timestamp1] timestamp2[$timestamp2]");
					break;
				}
				else
				{
					Log::info("SwooleHelper::Lock timeout,force get lock failed key[$cachekey] resource[$resource] timestamp1[$timestamp1] timestamp2[$timestamp2]");
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
	
	public static function run($address,$port=8888){
		//确保在连接客户端时不会超时
		ignore_user_abort();
		set_time_limit(0);
		
		$host = "0.0.0.0";
		
		try
		{
			$server = new Swoole\Server($host,$port);
			$serv->on('connect', function ($server, $fd){
				echo "Client:Connect.\n";
			});
			
			$server->on('receive', function ($server, $fd, $from_id, $data) {
				//$server->send($fd, 'Swoole: '.$data);
			});
				
			$server->on('close', function ($server, $fd) {
				echo "Client: Close.\n";		
			});
					
			$server->start();
		}
		catch (\Exception $e) {
			Log::info("SwooleHelper::Run Error [$address.$port] error[".$e->getMessage()."]");
		}
	}
	
	public static function writeShort(&$bin,$value)
	{
		$bin.=pack('v',$value);
	}
	
	public static function writeInt(&$bin,$value)
	{
		$bin.=pack('V',$value);
	}
	
	public static function writeString(&$bin,$value)
	{
		$arrayString=array_map('ord',str_split($value));
		foreach($arrayString as $vo){
			$bin.=pack('c',$vo);
		}
		//$bin.=pack('c','0');
	}
	
	public static function readShort(&$bin)
	{
		return unpack('v',$bin)[1];
	}
	
	public static function readInt(&$bin)
	{
		return unpack('V',$bin)[1];
	}
	
	public static function readString(&$bin,$len)
	{
		$byteArray = array();
		for($i=0;$i<$len;$i++)
		{
			array_push($byteArray, unpack('c', $bin));
		}
		return serialize(byteArray);
	}

	public static function sendJsonObject($sock,$json){
		$str = json_encode($json,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
		SwooleHelper::sendMessage($sock,$str);
	}
	
	public static function sendMessage($sock,$str){
		//        $str        = iconv('gbk','utf-8',$str);
		try {
			$len = strlen($str);
			$bin = '';
			SwooleHelper::writeInt($bin,$len);
			SwooleHelper::writeString($bin,$str);
			//socket_write($sock, $bin);
			
			if(!socket_write($sock, $bin,strlen($bin)))
				Log::info("socket_write() failed: data len [".strlen($bin)."] reason: " . socket_strerror($sock) . "\n");
			
		} catch (\Exception $e) {
			Log::info("sendMessage error socket[".intval($sock)."] error[".$e->getMessage()."]");
			SwooleHelper::closeSocket($sock);
		}
	}

	public static function receiveMessage($sock)
	{
		try {
			$buf = socket_read($sock,SwooleHelper::$headLen);
			$readLen = SwooleHelper::readInt($buf);
			if($readLen<536870912)
			{
				$buf = socket_read($sock,$readLen);
				SwooleHelper::dispatchMessage($sock,$buf);
			}
			else 
			{
				SwooleHelper::closeSocket($sock);
				Log::info("receiveMessage error socket[".intval($sock)."] Allowed memory size of 536870912 bytes exhausted (tried to allocate $readLen bytes)");
			}
		} catch (\Exception $e) {
			Log::info("receiveMessage error socket[".intval($sock)."] error[".$e->getMessage()."]");
			SwooleHelper::closeSocket($sock);
		}

// 		$recvBuf = SwooleHelper::$recvBufs[intval($sock)];
// 		if($recvBuf->bodyLen==0)
// 		{
// 			if(SwooleHelper::$headLen>$recvBuf->len)
// 			{
// 				$readLen = SwooleHelper::$headLen-$recvBuf->len;
// 				$buf = socket_read($sock,$readLen,PHP_NORMAL_READ);
// 				$readLen = SwooleHelper::readShort($buf);
// 				var_dump($readLen);
// 				$buf = socket_read($sock,$readLen,PHP_NORMAL_READ);
// 				var_dump($buf);
// 				exit;
// 				$result = SwooleHelper::readString($buf,$readLen);
// 				$bytes = socket_recv($sock,$buf,$readLen,PHP_NORMAL_READ);
// 				if ($bytes>0) {
// 					$recvBuf->buf.=$tmp;
// 					if ($bytes+$recvBuf->len==SwooleHelper::$headLen)
// 					{
// 						$recvBuf->bodyLen = SwooleHelper::readShort($recvBuf->buf);
// 						$recvBuf->buf='';
// 						$recvBuf->len = 0;
// 					}
// 				} else {
//     				Log::info("socket_recv() failed; reason: " . socket_strerror(socket_last_error($socket)) . "\n");
// 				}
// 			}
// 			else
// 			{
// 				socket_close($sock);
// 			}
// 		}
// 		if($recvBuf->bodyLen>0)
// 		{
// 			if (false !== ($bytes = socket_recv($sock, $recvBuf->buf+$recvBuf->len, SwooleHelper::$headLen, MSG_WAITALL))) {
// 				if ($bytes+$recvBuf->len==SwooleHelper::$headLen)
// 				{
// 					 $msg= SwooleHelper::readString($recvBuf->buf,$recvBuf->bodyLen);
// 					 $recvBuf->bodyLen = 0;
// 					 SwooleHelper::dispatchMessage($msg);
// 				}
// 			} else {
// 				Log::info("socket_recv() failed; reason: " . socket_strerror(socket_last_error($socket)) . "\n");
// 			}
// 		}

	}
	
	public static function dispatchMessage($sock,$msg)
	{
		$json = json_decode($msg);
		
		if($json->cmd==SwooleHelper::$CMD_CUSTOMER_ENTER)
		{
			$info = new \stdClass();
			$info->socket = $sock;
			$info->cid = $json->cid;
			$info->roomId = $json->roomId;
			$info->roomType = $json->roomType;
			$info->lastTick = microtime(true);
		
			SwooleHelper::$customers[$info->cid] = $info;
			
			if(($ret = socket_set_nonblock( $sock ))<0)
				Log::info("client socket socket_set_nonblock() 失败的原因是:".socket_strerror($ret));
			
			Log::info("Customer Enter socket[".intval($sock)."] cid[$info->cid] roomId[$info->roomId] roomType[$info->roomType]");
			
// 			SwooleHelper::sendMessage($info->socket,"{\"cmd\":1,\"result\":1}");
		}
		
		Log::info("receive message [$msg]");
	}
	
	public static function fetchMessages($customerInfo)
	{	
		$result = new \stdClass();
		$result->cmd = SwooleHelper::$CMD_ACTION_CMDS;
		$result->cid=$customerInfo->cid;
		$result->result = 1;
		$result->lastTick = $result->last_tick = microtime(true);
		$result->cmds = Array();
	 
		if($customerInfo->roomId!='0')
		{
			$startTime = UtilsHelper::getMillisecond();
			CmdHelper::CheckEventCmd($result->cmds, $customerInfo->cid);
			CmdHelper::CheckChatCmd($result->cmds, $customerInfo->roomId, $customerInfo->cid);
			CmdHelper::CheckGiftCmd($result->cmds, $customerInfo->roomId, $customerInfo->cid);
	
			if($customerInfo->roomType==1)
				CmdHelper::CheckAuctionCmd($result->cmds, $customerInfo->roomId, $customerInfo->cid);
			else
				CmdHelper::CheckPokderRbCmd($result->cmds, $customerInfo->roomId, $customerInfo->cid);
			
			$elapse = intval(UtilsHelper::getMillisecond()-$startTime);
			if($elapse>self::$CHECK_TIME_OUT)
			{
				Log::info("ServiceHelper::fetchMessages check time out cid[$customerInfo->cid] cmd num[".count($result->cmds)."] elapse[$elapse]");
			}
		}
	 
		if(count($result->cmds)>0)
		{
			$startTime = UtilsHelper::getMillisecond();
			$content = json_encode($result,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
			SwooleHelper::sendMessage($customerInfo->socket, $content);
			
			$elapse = intval(UtilsHelper::getMillisecond()-$startTime);
			if($elapse>self::$CHECK_TIME_OUT)
			{
				Log::info("ServiceHelper::fetchMessages send time out socket[$customerInfo->socket] cid[$customerInfo->cid] cmd num[".count($result->cmds)."] elapse[$elapse]");
			}
			
			//Log::info("fetchMessage cid[$customerInfo->cid] roomId[$customerInfo->roomId] content[$content]");
		}
// 		else 
// 		{
// 			Log::info("fetchMessage no message cid[$customerInfo->cid] roomId[$customerInfo->roomId]");
// 		}	
		//Log::info("fetchMessage cid[$result->cid]");
	}
	
	public static function closeSocket($sock)
	{
		foreach( SwooleHelper::$customers as $key=>$customerInfo )
		{
			if($customerInfo->socket==$sock)
			{
				unset(SwooleHelper::$customers[$key]);
			}
		}
		
		foreach( SwooleHelper::$clients as $key=>$client )
		{
			if($client==$sock)
			{
				unset(SwooleHelper::$clients[$key]);
			}
		}
		
		SwooleHelper::$customers = array_filter( SwooleHelper::$customers );
		SwooleHelper::$clients = array_filter( SwooleHelper::$clients );
		socket_close($sock);
		
		Log::info("closeSocket socket[".intval($sock)."]");
	}
	
	
	public static function GetSocketList()
	{
		self::Lock("SOCKET.LIST");
		$cachekey = CmdHelper::CACHE_SOCKET_PREFIX.".SOCKET.LIST";
		$record = Redis::connection('service')->get($cachekey);
		$array=null;
		if($record!=null && $record!="")
			$array = json_decode($record);
	
		if(is_object($array))
			$array = (array)$array;
	
		if(!is_array($array))
			$array = array();
	
		self::Unlock("SOCKET.LIST");
		return $array;
	}
	
	public static function ClearSocketList()
	{
		self::Lock("SOCKET.LIST");
		$cachekey = CmdHelper::CACHE_SOCKET_PREFIX.".SOCKET.LIST";
		$array = array();
		Redis::connection('service')->set($cachekey,json_encode($array,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK));
		self::Unlock("SOCKET.LIST");
	}
	
	public static function RegisterSocket($address,$port,$socket)
	{
		self::Lock("SOCKET.LIST");
		$cachekey = CmdHelper::CACHE_SOCKET_PREFIX.".SOCKET.LIST";
		$array=null;
		$record = Redis::connection('service')->get($cachekey);
		if($record!=null && $record!="")
			$array = json_decode($record);
	
		if(is_object($array))
			$array = (array)$array;
	
		if(!is_array($array))
			$array = array();
	
		$socketInfo = new \stdClass();
		$socketInfo->address = $address;
		$socketInfo->port = $port;
		$socketInfo->socket_id = intval($socket);
		array_push($array, $socketInfo);
		Redis::connection('service')->set($cachekey,json_encode($array,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK));
		self::Unlock("SOCKET.LIST");
	}
	
	public static function RemoveSocket($address,$port)
	{
		self::Lock("SOCKET.LIST");
		$cachekey = CmdHelper::CACHE_SOCKET_PREFIX.".SOCKET.LIST";
		$array=null;
		$record = Redis::connection('service')->get($cachekey);
		if($record!=null && $record!="")
			$array = json_decode($record);
	
		if(is_object($array))
			$array = (array)$array;
	
		if(!is_array($array))
			$array = array();
	
		$changed = false;
		foreach ($array as $key => $value) {
			if($value->address==$address && $value->port==$port)
			{
				unset($array[$key]);
				$changed = true;
			}
		}
						
		if($changed)
		{
			$array = array_values($array);
			Redis::connection('service')->set($cachekey,json_encode($array,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK));
		}
		self::Unlock("SOCKET.LIST");
	}
	
	public static function ExistSocket($address,$port)
	{
		$cachekey = CmdHelper::CACHE_SOCKET_PREFIX.".SOCKET.LIST";
		$array=null;
		$record = Redis::connection('service')->get($cachekey);
		if($record!=null && $record!="")
			$array = json_decode($record);
	
		if(is_object($array))
			$array = (array)$array;
	
		if(!is_array($array))
			$array = array();
	
		foreach ($array as $key => $value) {
			if($value->address==$address && $value->port==$port)
				return true;
		}
		
		return false;
	}
	
	
}