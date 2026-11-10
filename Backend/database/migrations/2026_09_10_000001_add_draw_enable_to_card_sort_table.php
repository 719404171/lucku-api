<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 卡池抽卡入口開關：1 開啟 / 0 關閉。
 * 可單獨關閉某個卡池，也可在後台「卡池管理」一鍵批量開關。
 */
return new class extends Migration {

    public function up(): void
    {
        try {
            if (!Schema::hasColumn('card_sort', 'draw_enable')) {
                Schema::table('card_sort', function (Blueprint $table) {
                    $table->tinyInteger('draw_enable')->default(1)->comment('抽卡入口開關 1:開啟 0:關閉');
                });
            }
        } catch (\Throwable $e) {
            // 表不存在等場景直接放過
        }
    }

    public function down(): void
    {
        try {
            Schema::table('card_sort', function (Blueprint $table) {
                $table->dropColumn('draw_enable');
            });
        } catch (\Throwable $e) {
            // 忽略
        }
    }
};
