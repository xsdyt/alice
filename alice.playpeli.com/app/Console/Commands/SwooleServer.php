<?php
namespace App\Console\Commands;

use Swoole;
use Redis;
use Illuminate\Console\Command;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Log;
use App\Helpers\CmdHelper;
use App\Helpers\UtilsHelper;
use App\Helpers\ServiceHelper;

class SwooleServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'swoole {address} {port}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display an test quote';
    
    const CMD_ONCONNECTED = 1;
    const CMD_CUSTOMER_ENTER = 2;
    const CMD_ACTION_CMDS = 3;
    
    const CHECK_EXIST_INTERVAL = 1;
    const CHECK_TIME_OUT = 50;
    
    const headLen = 4;
    
    
    public $address = "";
    public $port = 0;
    public $clients = array();
    public $serv = null;
    
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
    	$this->address = $this->argument('address');
    	$this->port = $this->argument("port");
    	
    	$serv = new Swoole\Server($this->address, $this->port);
    	$serv->set(array(
    			'worker_num' => 8,   //工作进程数量
    			'daemonize' => true, //是否作为守护进程
    	));
    	
    	
    	$process = new Swoole\Process(function($process) use ($serv) {
    		while (true) {
    			$startTime = UtilsHelper::getMillisecond();
    			ServiceHelper::UpdateServiceTime("SOCKET.$this->address.$this->port");
    			 
    			//Log::info("SwooleServer:: Call ServiceHelper::UpdateServiceTime [SOCKET.$this->address.$this->port]");
    			 
    			$elapse = intval(UtilsHelper::getMillisecond()-$startTime);
    			if($elapse>self::CHECK_TIME_OUT)
    				Log::info("SwooleServer::Run Update Service Time $this->address:$this->port elapse[$elapse]");
    				
    			usleep(100000);
    		}
    	});
    	
    	$serv->addProcess($process);
    	
    	$serv->on('start', function ($serv) {
    		Log::info("on Event Start $this->address:$this->port");
    	});
    	
    	$serv->on('connect', function ($serv, $fd){
    		$tickId = $serv->tick(100, function() use ($serv, $fd) {
    			$client = $this->clients[$fd];
    			if($client && isset($client->cid))
    			{
    				$this->fetchMessages($serv,$fd,$client);
    			}
    		});

    		$client = new \stdClass();
    		$client->fd = $fd;
    		$client->tickId = $tickId;
    		$client->recvBuf = '';
    		$client->bodyLen = 0;
    		$this->clients[$fd] = $client;
    		
    		$jsonObject = new \stdClass();
    		$jsonObject->cmd = self::CMD_ONCONNECTED;
    		$json = json_encode($jsonObject,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
    		$len = strlen($json);
    		$bin = '';
    		$bin.=pack('V',$len);
    		$arrayString=array_map('ord',str_split($json));
    		foreach($arrayString as $vo){
    			$bin.=pack('c',$vo);
    		}
    		$serv->send($fd, $bin);
    		
    		$info = $serv->connection_info($fd);
     		$remoteAddress = $info["remote_ip"];
     		$remotePort = $info["remote_port"];
     		Log::info( 'Accept a new incoming connection fd['.$fd.'] '." server[$this->address:$this->port] client[$remoteAddress:$remotePort]");
    	});
    		
    	$serv->on('receive', function ($serv, $fd, $from_id, $data) {
    		if(array_key_exists($fd, $this->clients))
    		{
    			$client = $this->clients[$fd];
    			if($client)
    			{
    				$client->recvBuf.=$data;
    				
    				$bufLen = strlen($client->recvBuf);
    				if($client->bodyLen==0 && $bufLen>=self::headLen)
    				{
    					$client->bodyLen=unpack('V',$client->recvBuf)[1];
    				}
    				
    				if($client->bodyLen>0 && $bufLen>=$client->bodyLen+self::headLen)
    				{
    					$byteArray = unpack("V/c$client->bodyLen",$client->recvBuf);
 						$byteArray=array_map('chr',$byteArray);
 						$content = implode("",$byteArray);
					
						$this->dispatchMessage($fd,$content);
						
						if($bufLen>$client->bodyLen+self::headLen)
						{
							$client->recvBuf = substr($client->recvBuf,$client->bodyLen+self::headLen,$bufLen-$client->bodyLen-self::headLen);
						}
						else 
						{
							$client->recvBuf = '';
						}
						
						$bufLen>$client->bodyLen = 0;
    				}
    			}
    		}
    	});
    	
    	$serv->on('close', function ($serv, $fd) {
    		
    		if(array_key_exists($fd, $this->clients))
    		{
    			$client = $this->clients[$fd];
    			if($client)
    				$serv->clearTimer($client->tickId);
    			$this->clients[$fd]=null;	
    		}
    			
    		$info = $serv->connection_info($fd);

    		$remoteAddress = $info["remote_ip"];
    		$remotePort = $info["remote_port"];
    		Log::info('Close connection fd['.$fd.'] '." server[$this->address:$this->port] client[$remoteAddress:$remotePort]");
    	});
    
    	$serv->on('managerStart', function($serv){
    		Log::info("on Event managerStart $this->address:$this->port");
    	});
    	
    	$serv->on('managerStop', function($serv){
    		Log::info("on Event managerStop $this->address:$this->port");
			$this->RemoveSocket($this->address,$this->port);
    	});
    	    	
    	if(!$this->ExistSocket($this->address,$this->port))
    		$this->RegisterSocket($this->address,$this->port);
	
    	$serv->start();
    
        $this->comment("swool server $this->address:$this->port!");
    }

    public function Lock($resource,$expire=5,$sleep=10000)
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
    				Log::info("SwooleServer::Lock timeout,force get lock success key[$cachekey] resource[$resource] timestamp1[$timestamp1] timestamp2[$timestamp2]");
    				break;
    			}
    			else
    			{
    				Log::info("SwooleServer::Lock timeout,force get lock failed key[$cachekey] resource[$resource] timestamp1[$timestamp1] timestamp2[$timestamp2]");
    			}
    			Redis::connection('service')->expire($cachekey,$expire+1);
    		}
    		usleep($sleep);
    	}
    }
    
    public function Unlock($resource)
    {
    	$cachekey = CmdHelper::CACHE_CMD_PREFIX.".lock.".$resource;
    	Redis::connection('service')->del($cachekey);
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
    
    public  function sendJsonObject($serv,$fd,$json){
    	$str = json_encode($json,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
    	$this->sendMessage($serv,$fd,$str);
    }
    
    public function dispatchMessage($fd,$msg)
    {
    	$json = json_decode($msg);
    
    	if(is_object($json) && $json->cmd==self::CMD_CUSTOMER_ENTER)
    	{
    		$client = $this->clients[$fd];
    		if($client)
    		{
    			$client->cid = $json->cid;
    			$client->roomId = $json->roomId;
    			$client->roomType = $json->roomType;
    			$client->lastTick = microtime(true);
    		}
    		
    		Log::info("Customer Enter fd[$fd] cid[$json->cid] roomId[$json->roomId] roomType[$json->roomType]");
    	}
    
    	Log::info("receive message [$msg]");
    }
    
    public function fetchMessages($serv,$fd,$client)
    {
    	$result = new \stdClass();
    	$result->cmd = self::CMD_ACTION_CMDS;
    	$result->cid=$client->cid;
    	$result->result = 1;
    	$result->lastTick = $result->last_tick = microtime(true);
    	$result->cmds = Array();
    
    	if($client->roomId!='0')
    	{
    		$startTime = UtilsHelper::getMillisecond();
    		CmdHelper::CheckEventCmd($result->cmds, $client->cid);
    		CmdHelper::CheckChatCmd($result->cmds, $client->roomId, $client->cid);
    		CmdHelper::CheckGiftCmd($result->cmds, $client->roomId, $client->cid);
    
    		if($client->roomType==1)
    			CmdHelper::CheckAuctionCmd($result->cmds, $client->roomId, $client->cid);
    		else
    			CmdHelper::CheckPokderRbCmd($result->cmds, $client->roomId, $client->cid);
    					
    		$elapse = intval(UtilsHelper::getMillisecond()-$startTime);
    		if($elapse>self::CHECK_TIME_OUT)
    		{
    			Log::info("ServiceHelper::fetchMessages check time out cid[$client->cid] cmd num[".count($result->cmds)."] elapse[$elapse]");
    		}
    	}
    
    	if(count($result->cmds)>0)
    	{
    		$startTime = UtilsHelper::getMillisecond();
    		$content = json_encode($result,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
    		
    		$this->sendMessage($serv,$fd,$content);
    			
    		$elapse = intval(UtilsHelper::getMillisecond()-$startTime);
    		if($elapse>self::CHECK_TIME_OUT)
    		{
    			Log::info("ServiceHelper::fetchMessages send time out fd[$client->fd] cid[$client->cid] cmd num[".count($result->cmds)."] elapse[$elapse]");
    		}
    	}
    }
    
    public function sendMessage($serv,$fd,$str){
    	try {
    		$len = strlen($str);
    		$bin = '';
    		$this->writeInt($bin,$len);
    		$this->writeString($bin,$str);
    		$serv->send($fd,$bin);
    	} catch (\Exception $e) {
    		Log::info("sendMessage error fd[".$fd."] error[".$e->getMessage()."]");
    	}
    }
    
    
    public function GetSocketList()
    {
    	$this->Lock("SOCKET.LIST");
    	$cachekey = CmdHelper::CACHE_SOCKET_PREFIX.".SOCKET.LIST";
    	$record = Redis::connection('service')->get($cachekey);
    	$array=null;
    	if($record!=null && $record!="")
    		$array = json_decode($record);
    
    	if(is_object($array))
    		$array = (array)$array;
    
    	if(!is_array($array))
    		$array = array();
    
    	$this->Unlock("SOCKET.LIST");
    	return $array;
    }
    
    public function ClearSocketList()
    {
    	$this->Lock("SOCKET.LIST");
    	$cachekey = CmdHelper::CACHE_SOCKET_PREFIX.".SOCKET.LIST";
    	$array = array();
    	Redis::connection('service')->set($cachekey,json_encode($array,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK));
    	$this->Unlock("SOCKET.LIST");
    }
    
    public function RegisterSocket($address,$port)
    {
    	$this->Lock("SOCKET.LIST");
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
		$socketInfo->socket_id = 0;
    	array_push($array, $socketInfo);
    	Redis::connection('service')->set($cachekey,json_encode($array,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK));
    	$this->Unlock("SOCKET.LIST");
    }
    
    public function RemoveSocket($address,$port)
    {
    	$this->Lock("SOCKET.LIST");
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
    	$this->Unlock("SOCKET.LIST");
    }
    
    public function ExistSocket($address,$port)
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