<?php

namespace App\Jobs;

use App\Models\Card;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * 异步补建抽卡订单：HTTP 请求阶段1（扣款/扣库存/写 task）后由队列执行。
 * 建单失败会按 tries/backoff 自动重试；仍失败则走 failed() 告警，task 留在表里可人工/命令补偿。
 */
class BuildCardOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $backoff = 2;

    public function __construct(public int $taskId)
    {
    }

    public function handle(Card $card): void
    {
        $result = $card->processDrawOrderTask($this->taskId);
        if (!$result['ok']) {
            // 抛出异常触发 Laravel 队列重试（tries=5）
            throw new \RuntimeException('build card order failed: ' . ($result['error'] ?? 'unknown'));
        }
    }

    public function failed(\Throwable $e): void
    {
        // 所有重试失败后：钱已扣，订单未建，需人工介入
        \Log::error('BuildCardOrderJob failed', [
            'task_id' => $this->taskId,
            'error'   => $e->getMessage(),
        ]);
    }
}
