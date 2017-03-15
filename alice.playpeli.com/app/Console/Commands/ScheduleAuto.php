<?php

namespace App\Console\Commands;
use Log;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use App\Helpers\TestHelper;
use App\Helpers\PokerRbHelper;
//use App\Helpers\AuctionHelper;

class ScheduleAuto extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scheduleAuto';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'alice 排班中的红与黑或拍卖自动执行（一个产品结束，另一个产品自动开启）';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
       // Log::info('scheduleAuto 任务调度');
        PokerRbHelper::AutoPokerRb();
       // AuctionHelper::AutoAuction();
    }
}
