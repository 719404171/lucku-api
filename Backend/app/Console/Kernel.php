<?php
/*
 * File: Kernel.php
 * Created Time: 2023-10-12 3:11:16 pm
 * Author: Wang Yu Qiang (719404171@qq.com)
 * -----
 * Last Modified: 2023-11-14 3:47:50 pm
 * Modified By: Wang Yu Qiang (719404171@qq.com>)
 * -----
 * Copyright 2023 - 2023 Brainstorm Ltd, 成都睿創科技有限公司
 */


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
        \App\Console\Commands\FillCardFieldsFromSpider::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // 抽卡預設順序表自動預熱：每 10 分鐘檢查一次，僅在順序表為空時才重建（開銷極低），
        // 避免預設表資料異常/清空時抽卡退化為隨機或首次請求變慢。
        $schedule->command('draw-preset:rebuild --if-needed')
            ->everyTenMinutes()
            ->withoutOverlapping();

        // 卡池抽卡價格自動更新：每分鐘觸發，命令內部比對 app_settings 的
        // auto_update_time1 / auto_update_time2（如 05:00、18:00），命中時間點才執行，
        // 僅更新 auto_update_price=1 的卡池，並有分鐘級冪等保護。
        $schedule->command('cards:update-draw-price')
            ->everyMinute()
            ->withoutOverlapping();

        // 用戶請求溯源日誌清理：每天凌晨 3 點刪除 30 天前的記錄，避免 user_request_log 無限增長
        $schedule->call(function () {
            \Illuminate\Support\Facades\DB::table('user_request_log')
                ->where('created_at', '<', date('Y-m-d H:i:s', strtotime('-30 days')))
                ->delete();
        })->dailyAt('03:00');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
