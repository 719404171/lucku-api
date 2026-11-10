<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 卡池抽卡价格：手动百分比 + 每日自动更新开关 + 最后更新时间
 *
 * 公式：抽卡价格（取整）= 卡牌总价 ÷ 卡牌数量 × 百分比 / 100
 * - draw_price_percent：手动百分比，默认 100
 * - auto_update_price：是否每日按 app_settings.auto_update_time1/2 自动更新，默认 1
 * - draw_price_updated_at：抽卡价格最后更新时间（定时任务据此防止同一时间窗重复执行）
 */
return new class extends Migration {

    public function up(): void
    {
        if (!Schema::hasColumn('card_sort', 'draw_price_percent')) {
            Schema::table('card_sort', function (Blueprint $table) {
                $table->decimal('draw_price_percent', 5, 2)->default(100.00)->after('card_draw_price')
                    ->comment('抽卡价格手动百分比：抽卡价格=卡牌总价/卡牌数量*百分比/100');
            });
        }
        if (!Schema::hasColumn('card_sort', 'auto_update_price')) {
            Schema::table('card_sort', function (Blueprint $table) {
                $table->tinyInteger('auto_update_price')->default(1)->after('draw_price_percent')
                    ->comment('是否每日自动更新抽卡价格：1=是 0=否');
            });
        }
        if (!Schema::hasColumn('card_sort', 'draw_price_updated_at')) {
            Schema::table('card_sort', function (Blueprint $table) {
                $table->dateTime('draw_price_updated_at')->nullable()->after('auto_update_price')
                    ->comment('抽卡价格最后更新时间');
            });
        }
    }

    public function down(): void
    {
        Schema::table('card_sort', function (Blueprint $table) {
            $table->dropColumn(['draw_price_percent', 'auto_update_price', 'draw_price_updated_at']);
        });
    }
};
