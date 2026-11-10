<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 鑽石兌換金幣開關：1 = 開放（App 可點鑽石兌換金幣）/ 0 = 停止（不可再兌換）。
 * 後台「店鋪管理 → 店鋪設置」可切換。
 */
return new class extends Migration {

    public function up(): void
    {
        try {
            if (!Schema::hasColumn('shop_setting', 'diamond_exchange_enable')) {
                Schema::table('shop_setting', function (Blueprint $table) {
                    $table->tinyInteger('diamond_exchange_enable')->default(1)->comment('鑽石兌換金幣開關 1:開放 0:停止');
                });
            }
        } catch (\Throwable $e) {
            // 表不存在等場景直接放過
        }
    }

    public function down(): void
    {
        try {
            if (Schema::hasColumn('shop_setting', 'diamond_exchange_enable')) {
                Schema::table('shop_setting', function (Blueprint $table) {
                    $table->dropColumn('diamond_exchange_enable');
                });
            }
        } catch (\Throwable $e) {
            // 忽略
        }
    }
};
