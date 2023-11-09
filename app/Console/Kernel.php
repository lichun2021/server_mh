<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;

class Kernel extends \Illuminate\Foundation\Console\Kernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        'App\Console\Commands\Collect',
        'App\Console\Commands\DownloadSkinsImage',
        'App\Console\Commands\PriceSync',
        'App\Console\Commands\GameArenaBot',
        'App\Console\Commands\RoomSettlement',
        'App\Console\Commands\OpenBoxBot',
        'App\Console\Commands\AutoRecharge',
        'App\Console\Commands\RoomTimed',
        'App\Console\Commands\SendSkins',
        'App\Console\Commands\BaiduRecall'
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //福利房结算
        $schedule->command('room-settlement')->everyMinute();
        //主播充值自动到账
        $schedule->command('auto-recharge')->everyMinute();
        //开箱机器人
        $schedule->command('open-box-bot')->everyMinute();
        //Roll定时上架
        $schedule->command('room-timed')->everyMinute();
        //自动发货
        $schedule->command('youpin:send-skins')->everyMinute();
        //饰品采集->图片下载->价格同步
        $schedule->command('youpin:collect')->dailyAt('2:00');
        //价格同步
        $schedule->command('youpin:price-sync')->dailyAt('10:00');
        //V5订单状态同步
        $schedule->command('v5:state-syn')->everyMinute();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
