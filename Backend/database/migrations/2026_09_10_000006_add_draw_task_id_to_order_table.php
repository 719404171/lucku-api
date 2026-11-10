<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 訂單表記錄抽卡批次號，讓後台對賬能把「抽卡批次 → 訂單 → 卡牌明細」精確關聯起來。
 * 歷史訂單無法可靠回填（舊數據沒有該欄位），後台對賬會自動退化为「用戶+時間窗」近似關聯。
 */
return new class extends Migration {

    public function up(): void
    {
        if (!Schema::hasColumn('order', 'draw_task_id')) {
            Schema::table('order', function (Blueprint $table) {
                $table->bigInteger('draw_task_id')->default(0)->comment('關聯抽卡批次ID(card_draw_tasks.task_id)，非抽卡為0');
                $table->index('draw_task_id', 'idx_order_draw_task');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('order', 'draw_task_id')) {
            Schema::table('order', function (Blueprint $table) {
                $table->dropIndex('idx_order_draw_task');
                $table->dropColumn('draw_task_id');
            });
        }
    }
};
