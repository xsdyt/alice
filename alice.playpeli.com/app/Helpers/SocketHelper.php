<?php
namespace App\Helpers;

use Redis;
use Illuminate\Support\Facades\Log;
use App\Helpers\CmdHelper;
use App\Helpers\UtilsHelper;
use App\Helpers\ServiceHelper;

class SocketHelper
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
					Log::info("SocketHelper::Lock timeout,force get lock success key[$cachekey] resource[$resource] timestamp1[$timestamp1] timestamp2[$timestamp2]");
					break;
				}
				else
				{
					Log::info("SocketHelper::Lock timeout,force get lock failed key[$cachekey] resource[$resource] timestamp1[$timestamp1] timestamp2[$timestamp2]");
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
		
		/*
		 +-------------------------------
		 *    @socket通信整个过程
		 +-------------------------------
		 *    @socket_create
		 *    @socket_bind
		 *    @socket_listen
		 *    @socket_accept
		 *    @socket_read
		 *    @socket_write
		 *    @socket_close
		 +--------------------------------
		 */
		
		/*----------------    以下操作都是手册上的    -------------------*/
		if(($sock = socket_create(AF_INET,SOCK_STREAM,SOL_TCP)) < 0) {
			Log::info("socket_create() 失败的原因是:".socket_strerror($sock));
			exit;
		}
		
		try
		{
			if(($ret = socket_set_option($sock, SOL_SOCKET, SO_REUSEADDR, 1))<0)//1表示接受所有的数据包
			{
				Log::info("socket_set_nonblock() 失败的原因是:".socket_strerror($ret));
				exit;
			}
		
			if(($ret = socket_bind($sock,$host,$port)) < 0) {
				Log::info("socket_bind() 失败的原因是:".socket_strerror($ret));
				exit;
			}
		
			if(($ret = socket_listen($sock,4)) < 0) {
				Log::info("socket_listen() 失败的原因是:".socket_strerror($ret));
				exit;
			}
		
			if(($ret = socket_set_nonblock( $sock ))<0){
				Log::info("socket_set_nonblock() 失败的原因是:".socket_strerror($ret));
				exit;
			}
		
			self::RegisterSocket($address,$port,$sock);
			Log::info("socket listen success [$address:$port]");
			$count = 0;
			$lastCheckExist = time();
			do {
				$startTime = UtilsHelper::getMillisecond();
				//Log::info("ServiceHelper::Run Start Do $address.$port start[$startTime]");
				$currentTime = time();
				if($currentTime-$lastCheckExist>self::$CHECK_EXIST_INTERVAL)
				{
					if(!self::ExistSocket($address, $port))
						break;
					$lastCheckExist = $currentTime;
				}
				$elapse = intval(UtilsHelper::getMillisecond()-$startTime);
				if($elapse>self::$CHECK_TIME_OUT)
 					Log::info("ServiceHelper::Run Check Exist $address.$port elapse[$elapse]");	
				
 				$startTime = UtilsHelper::getMillisecond();
				ServiceHelper::UpdateServiceTime("SOCKET.$address.$port");
				$elapse = intval(UtilsHelper::getMillisecond()-$startTime);
				if($elapse>self::$CHECK_TIME_OUT)
					Log::info("ServiceHelper::Run Update Service Time $address.$port elapse[$elapse]");
				
				$startTime = UtilsHelper::getMillisecond();
				$arrayReads = array_merge( array( $sock ), SocketHelper::$clients );
				$elapse = intval(UtilsHelper::getMillisecond()-$startTime);
				if($elapse>self::$CHECK_TIME_OUT)
					Log::info("ServiceHelper::Run Read Client Merge $address.$port elapse[$elapse]");
				
				$startTime = UtilsHelper::getMillisecond();
				//Log::info("ServiceHelper::Run Start Select $address.$port start[$startTime]");
				
				if(($ret = socket_select( $arrayReads, $arrayWrites, $arrayExcepts, 0 ))>=0 )
				{
					if( in_array( $sock, $arrayReads ) )
					{
						if( ( $clientSock = @socket_accept( $sock ) ) )
						{
// 							if(($ret = socket_set_nonblock( $clientSock ))<0){
// 								Log::info("client socket socket_set_nonblock() 失败的原因是:".socket_strerror($ret));
// 							}
							
							array_push(SocketHelper::$clients, $clientSock);
							
							$jsonObject = new \stdClass();
							$jsonObject->cmd = SocketHelper::$CMD_ONCONNECTED;
							SocketHelper::sendJsonObject($clientSock,$jsonObject);
			
							if(($ret = socket_getpeername( $clientSock, $remoteAddress, $remotePort ))<0)
							{
								$remoteAddress = "???.???.???.???";
								$remotePort = "????";
							}
							Log::info( 'Accept a new incoming connection socket['.intval($clientSock).'] '." server[$address.$port] client[$remoteAddress:$remotePort]");
						}
						else
						{
							Log::info( 'Could not accept a new connection  socket['.intval($clientSock).']'." server[$address.$port] ".' ( '.socket_strerror( socket_last_error() ).' ).' );
						}
					}
				}
				else
				{
					Log::info("socket_select() 失败的原因是:".socket_strerror($ret));
				}
			
				$elapse = intval(UtilsHelper::getMillisecond()-$startTime);
				if($elapse>self::$CHECK_TIME_OUT)
					Log::info("ServiceHelper::Run Select $address.$port elapse[$elapse]");
				
				$startTime = UtilsHelper::getMillisecond();
				//Log::info("ServiceHelper::Run Start Receive $address.$port start[$startMicroTime]");
				foreach( SocketHelper::$clients as $client )
				{
					if( in_array( $client, $arrayReads ) )
					{
						SocketHelper::receiveMessage($client);
					}
				}

				SocketHelper::$clients = array_filter( SocketHelper::$clients );
			
				$elapse = intval(UtilsHelper::getMillisecond()-$startTime);
				if($elapse>self::$CHECK_TIME_OUT)
					Log::info("ServiceHelper::Run Receive $address.$port elapse[$elapse]");
				
				$startTime = UtilsHelper::getMillisecond();
				$customerNum = count(SocketHelper::$customers);
				//Log::info("ServiceHelper::Run Start Fetch[$address.$port] customerNum[$customerNum] start[$startMicroTime]");
				if($customerNum>0)
				{
					foreach( SocketHelper::$customers as $customerInfo )
					{
						if($customerInfo->cid>0 && $customerInfo->lastTick+200<UtilsHelper::getMillisecond())
						{
							SocketHelper::fetchMessages($customerInfo);
							$customerInfo->lastTick = UtilsHelper::getMillisecond();
						}
					}
				//Log::info("Check Messages customers count[$customerNum]");
				}
				$elapse = intval(UtilsHelper::getMillisecond()-$startTime);
				if($elapse>self::$CHECK_TIME_OUT)
					Log::info("ServiceHelper::Run FetchMessages [$address.$port] customerNum[$customerNum] elapse[$elapse]");
				
				usleep(20000);
				
			} while (true);
		
			self::RemoveSocket($address,$port);
			socket_close($sock);
		}
		catch (\Exception $e) {
			self::RemoveSocket($address,$port);
			socket_close($sock);
			Log::info("ServiceHelper::Run Error [$address.$port] error[".$e->getMessage()."]");
		}
		
		Log::info("socket close success [$address:$port]");
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
		return serialize($byteArray);
	}

	public static function sendJsonObject($sock,$json){
		$str = json_encode($json,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
		SocketHelper::sendMessage($sock,$str);
	}
	
	public static function sendMessage($sock,$str){
		//        $str        = iconv('gbk','utf-8',$str);
		try {
			$len = strlen($str);
			$bin = '';
			SocketHelper::writeInt($bin,$len);
			SocketHelper::writeString($bin,$str);
			//socket_write($sock, $bin);
			
			if(!socket_write($sock, $bin,strlen($bin)))
				Log::info("socket_write() failed: data len [".strlen($bin)."] reason: " . socket_strerror($sock) . "\n");
			
		} catch (\Exception $e) {
			Log::info("sendMessage error socket[".intval($sock)."] error[".$e->getMessage()."]");
			SocketHelper::closeSocket($sock);
		}
	}

	public static function receiveMessage($sock)
	{
		try {
			$buf = socket_read($sock,SocketHelper::$headLen);
			$readLen = SocketHelper::readInt($buf);
			if($readLen<536870912)
			{
				$buf = socket_read($sock,$readLen);
				SocketHelper::dispatchMessage($sock,$buf);
			}
			else 
			{
				SocketHelper::closeSocket($sock);
				Log::info("receiveMessage error socket[".intval($sock)."] Allowed memory size of 536870912 bytes exhausted (tried to allocate $readLen bytes)");
			}
		} catch (\Exception $e) {
			Log::info("receiveMessage error socket[".intval($sock)."] error[".$e->getMessage()."]");
			SocketHelper::closeSocket($sock);
		}

// 		$recvBuf = SocketHelper::$recvBufs[intval($sock)];
// 		if($recvBuf->bodyLen==0)
// 		{
// 			if(SocketHelper::$headLen>$recvBuf->len)
// 			{
// 				$readLen = SocketHelper::$headLen-$recvBuf->len;
// 				$buf = socket_read($sock,$readLen,PHP_NORMAL_READ);
// 				$readLen = SocketHelper::readShort($buf);
// 				var_dump($readLen);
// 				$buf = socket_read($sock,$readLen,PHP_NORMAL_READ);
// 				var_dump($buf);
// 				exit;
// 				$result = SocketHelper::readString($buf,$readLen);
// 				$bytes = socket_recv($sock,$buf,$readLen,PHP_NORMAL_READ);
// 				if ($bytes>0) {
// 					$recvBuf->buf.=$tmp;
// 					if ($bytes+$recvBuf->len==SocketHelper::$headLen)
// 					{
// 						$recvBuf->bodyLen = SocketHelper::readShort($recvBuf->buf);
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
// 			if (false !== ($bytes = socket_recv($sock, $recvBuf->buf+$recvBuf->len, SocketHelper::$headLen, MSG_WAITALL))) {
// 				if ($bytes+$recvBuf->len==SocketHelper::$headLen)
// 				{
// 					 $msg= SocketHelper::readString($recvBuf->buf,$recvBuf->bodyLen);
// 					 $recvBuf->bodyLen = 0;
// 					 SocketHelper::dispatchMessage($msg);
// 				}
// 			} else {
// 				Log::info("socket_recv() failed; reason: " . socket_strerror(socket_last_error($socket)) . "\n");
// 			}
// 		}

	}
	
	public static function dispatchMessage($sock,$msg)
	{
		$json = json_decode($msg);
		
		if($json->cmd==SocketHelper::$CMD_CUSTOMER_ENTER)
		{
			$info = new \stdClass();
			$info->socket = $sock;
			$info->cid = $json->cid;
			$info->roomId = $json->roomId;
			$info->roomType = $json->roomType;
			$info->lastTick = microtime(true);
		
			SocketHelper::$customers[$info->cid] = $info;
			
			if(($ret = socket_set_nonblock( $sock ))<0)
				Log::info("client socket socket_set_nonblock() 失败的原因是:".socket_strerror($ret));
			
			Log::info("Customer Enter socket[".intval($sock)."] cid[$info->cid] roomId[$info->roomId] roomType[$info->roomType]");
			
// 			SocketHelper::sendMessage($info->socket,"{\"cmd\":1,\"result\":1}");
		}
		
		Log::info("receive message [$msg]");
	}
	
	public static function fetchMessages($customerInfo)
	{	
		$result = new \stdClass();
		$result->cmd = SocketHelper::$CMD_ACTION_CMDS;
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
			SocketHelper::sendMessage($customerInfo->socket, $content);
			
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
		foreach( SocketHelper::$customers as $key=>$customerInfo )
		{
			if($customerInfo->socket==$sock)
			{
				unset(SocketHelper::$customers[$key]);
			}
		}
		
		foreach( SocketHelper::$clients as $key=>$client )
		{
			if($client==$sock)
			{
				unset(SocketHelper::$clients[$key]);
			}
		}
		
		SocketHelper::$customers = array_filter( SocketHelper::$customers );
		SocketHelper::$clients = array_filter( SocketHelper::$clients );
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