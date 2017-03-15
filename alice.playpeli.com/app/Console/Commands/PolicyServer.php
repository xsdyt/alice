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

class PolicyServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'policy {address} {port}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display an test quote';
    
    
    public $address = "";
    public $port = 0;

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
    			'worker_num' => 2,   //工作进程数量
    			'daemonize' => true, //是否作为守护进程
    	));
    	
    	$serv->on('start', function ($serv) {
    		Log::info("on Event Start $this->address:$this->port");
    	});
    	
    	$serv->on('connect', function ($serv, $fd){
    		$content = "<?xml version=\"1.0\"?><cross-domain-policy><allow-access-from domain=\"*\" to-ports=\"*\"/></cross-domain-policy>";
    		$serv->send($fd,$content);
    		$info = $serv->connection_info($fd);
     		$remoteAddress = $info["remote_ip"];
     		$remotePort = $info["remote_port"];
     		Log::info( 'Accept policy connection fd['.$fd.'] '." server[$this->address:$this->port] client[$remoteAddress:$remotePort]");
     		
     		$serv->close($fd);
    	});
    		
    	$serv->on('receive', function ($serv, $fd, $from_id, $data) {
    	});
    	
    	$serv->on('close', function ($serv, $fd) {
    		$info = $serv->connection_info($fd);
    		$remoteAddress = $info["remote_ip"];
    		$remotePort = $info["remote_port"];
    		Log::info('Close policy connection fd['.$fd.'] '." server[$this->address:$this->port] client[$remoteAddress:$remotePort]");
    	});
    
    	$serv->on('managerStart', function($serv){
    		Log::info("on Event managerStart $this->address:$this->port");
    	});
    	
    	$serv->on('managerStop', function($serv){
    		Log::info("on Event managerStop $this->address:$this->port");
    	});

    	$serv->start();
    
        $this->comment("swool server $this->address:$this->port!");
    }
}