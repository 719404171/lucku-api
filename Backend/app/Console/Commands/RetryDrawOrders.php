<?php

namespace App\Console\Commands;

use App\Models\Card;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * 抽卡订单补偿：处理「阶段1已扣款/扣库存但阶段2建单未完成」的 card_draw_tasks。
 * 建议每分钟由 cron 执行一次：* * * * * cd /www/wwwroot/xxxx/Backend && php artisan draw-order:retry >> storage/logs/draw-order-retry.log 2>&1
 */
class RetryDrawOrders extends Command
{
    protected $signature = 'draw-order:retry {--limit=100}';

    protected $description = '补偿抽卡订单：把状态为待建单(0)的 card_draw_tasks 生成订单';

    public function handle(): int
    {
        $limit = (int)$this->option('limit');
        $now = date('Y-m-d H:i:s');

        $tasks = DB::table('card_draw_tasks')
            ->where('status', 0)
            ->orderBy('task_id')
            ->limit($limit)
            ->get();

        if ($tasks->isEmpty()) {
            $this->info('no pending task');
            return 0;
        }

        $card = new Card();
        foreach ($tasks as $task) {
            if ((int)$task->retry_times >= 10) {
                // 重试超限，标记待人工处理，避免无限重试
                DB::table('card_draw_tasks')
                    ->where('task_id', $task->task_id)
                    ->update(['status' => 2, 'updated_at' => $now]);
                $this->error("task {$task->task_id} exceed retry limit, marked as manual");
                continue;
            }

            $result = $card->processDrawOrderTask((int)$task->task_id);
            if ($result['ok'] && !empty($result['order_ids'])) {
                $this->info("task {$task->task_id} done, orders: " . implode(',', $result['order_ids']));
            } elseif ($result['ok']) {
                // 可能已被其他进程/请求处理（status=1）
                $this->warn("task {$task->task_id} already processed by others");
            } else {
                DB::table('card_draw_tasks')
                    ->where('task_id', $task->task_id)
                    ->increment('retry_times');
                $this->error("task {$task->task_id} failed: " . $result['error']);
            }
        }

        return 0;
    }
}
