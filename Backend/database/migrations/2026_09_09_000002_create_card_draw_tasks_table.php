<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        // 抽卡订单生成任务（两阶段事务的补偿表）：阶段1扣款/扣库存后落一条任务，
        // 阶段2建单成功置 status=1；阶段2失败/进程中断时由 draw-order:retry 命令补偿，
        // 保证「钱扣了必有单」。
        try {
            Schema::create('card_draw_tasks', function (Blueprint $table) {
                $table->bigIncrements('task_id');
                $table->bigInteger('user_id')->default(0);
                $table->bigInteger('card_sort_id')->default(0);
                $table->decimal('total_cost', 10, 2)->default(0);
                $table->mediumText('cards_json')->comment('抽中的卡牌原始数据');
                $table->tinyInteger('status')->default(0)->comment('0:待建单 1:已建单 2:重试超限待人工');
                $table->tinyInteger('retry_times')->default(0)->comment('补偿重试次数');
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->index(['user_id', 'status'], 'idx_dt_user_status');
                $table->index('card_sort_id', 'idx_dt_sort');
            });
        } catch (\Throwable $e) {
            // 表已存在等场景直接放過
        }
    }

    public function down(): void
    {
        try {
            Schema::dropIfExists('card_draw_tasks');
        } catch (\Throwable $e) {
            // 忽略
        }
    }
};
