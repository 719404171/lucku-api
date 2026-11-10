<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 抽卡扣款流水與抽卡任務的關聯鍵，供後台「查賬」精確對賬。
 *
 * 抽卡時 user_wallet_record（扣款憑證）與 card_draw_tasks（抽卡任務）在同一事務寫入，
 * 但此前兩表沒有任何關聯欄位，對賬只能按「用戶 + 金額 + 時間」猜測匹配，不可靠。
 * 加索引後按 task_id 對賬為索引查詢，且抽卡路徑僅多寫一個欄位，不增加查詢次數。
 */
return new class extends Migration {

    public function up(): void
    {
        try {
            if (!Schema::hasColumn('user_wallet_record', 'draw_task_id')) {
                Schema::table('user_wallet_record', function (Blueprint $table) {
                    $table->bigInteger('draw_task_id')->default(0)->comment('關聯抽卡任務ID(card_draw_tasks.task_id)，非抽卡為0');
                    $table->index('draw_task_id', 'idx_uwr_draw_task');
                });
            }
        } catch (\Throwable $e) {
            // 欄位/索引已存在等場景直接放過
        }

        // 歷史數據回填：扣款流水與任務在同一秒、同一用戶、同一金額寫入
        try {
            if (Schema::hasTable('card_draw_tasks')) {
                DB::statement("
                    UPDATE user_wallet_record w
                    JOIN card_draw_tasks t
                      ON t.user_id = w.user_id
                     AND t.created_at = w.created_at
                     AND t.total_cost = w.wallet_money
                    SET w.draw_task_id = t.task_id
                    WHERE w.wallet_type = 1
                      AND w.wallet_explain = '抽取卡片'
                      AND w.draw_task_id = 0
                ");
            }
        } catch (\Throwable $e) {
            // 回填失敗不影響新數據寫入，忽略
        }
    }

    public function down(): void
    {
        try {
            Schema::table('user_wallet_record', function (Blueprint $table) {
                $table->dropIndex('idx_uwr_draw_task');
                $table->dropColumn('draw_task_id');
            });
        } catch (\Throwable $e) {
            // 忽略
        }
    }
};
