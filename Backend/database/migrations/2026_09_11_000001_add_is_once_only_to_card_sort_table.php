<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 優惠卡包標記：1 = 該卡池為優惠卡包（每個帳號僅能抽一次，且只能單抽）。
 * 後台「卡池管理」可逐個卡池開啟/關閉。
 */
return new class extends Migration {

    public function up(): void
    {
        try {
            if (!Schema::hasColumn('card_sort', 'is_once_only')) {
                Schema::table('card_sort', function (Blueprint $table) {
                    $table->tinyInteger('is_once_only')->default(0)->comment('優惠卡包 1:每帳號僅能抽一次(僅單抽) 0:否');
                });
            }
        } catch (\Throwable $e) {
            // 表不存在等場景直接放過
        }
    }

    public function down(): void
    {
        try {
            if (Schema::hasColumn('card_sort', 'is_once_only')) {
                Schema::table('card_sort', function (Blueprint $table) {
                    $table->dropColumn('is_once_only');
                });
            }
        } catch (\Throwable $e) {
            // 忽略
        }
    }
};
