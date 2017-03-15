<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\Inspire::class,
    	\App\Console\Commands\SwooleServer::class,
    	\App\Console\Commands\PolicyServer::class,
        \App\Console\Commands\ScheduleAuto::class,
    	\App\Console\Commands\Test::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')
        //          ->hourly();
        $schedule->command('scheduleAuto')->everyMinute()->withoutOverlapping();
        // everyMinute everyFiveMinute hourly daily dailyAt weekly monthly
                 
    }
}
