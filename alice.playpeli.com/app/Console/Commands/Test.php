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

class Test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test';

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
        $this->comment("hello test!");
    }
    
    
}