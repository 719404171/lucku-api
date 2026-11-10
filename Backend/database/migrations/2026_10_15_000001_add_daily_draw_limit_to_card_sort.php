<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 卡池每日抽取次數限制：
 * 1. card_sort 新增 daily_draw_limit（0 = 不限制；>0 時每用戶每日最多抽 N 次操作）
 * 2. 新建 user_daily_draw_count 按日記錄每用戶每卡池抽取次數
 *    每日0點自動重置的實現方式：以 draw_date（日期）為統計口徑，
 *    查詢/累加永遠只針對「今天」的行，跨天自然寫入新行，無需定時任務清零。
 *    與原有 draw_limit_per_user（總次數限制）互相獨立、同時生效。
 */
return new class extends Migration {

    public function up(): void
    {
        if (Schema::hasTable('card_sort') && !Schema::hasColumn('card_sort', 'daily_draw_limit')) {
            Schema::table('card_sort', function (Blueprint $table) {
                $table->integer('daily_draw_limit')->default(0)->comment('每用戶每日抽取次數限制（0=不限制，每日0點重置）')->after('draw_limit_per_user');
            });
        }

        if (!Schema::hasTable('user_daily_draw_count')) {
            Schema::create('user_daily_draw_count', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->bigInteger('user_id')->default(0)->comment('用戶ID');
                $table->integer('card_sort_id')->default(0)->comment('卡池ID');
                $table->date('draw_date')->comment('統計日期（每日0點隨日期切換自然重置）');
                $table->integer('draw_count')->default(0)->comment('當日已抽取操作次數（單抽/10連/100連各計1次）');
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();

                $table->unique(['user_id', 'card_sort_id', 'draw_date'], 'uniq_uddc_user_sort_date');
                $table->index(['card_sort_id', 'draw_date'], 'idx_uddc_sort_date');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('user_daily_draw_count')) {
            Schema::dropIfExists('user_daily_draw_count');
        }
        if (Schema::hasTable('card_sort') && Schema::hasColumn('card_sort', 'daily_draw_limit')) {
            Schema::table('card_sort', function (Blueprint $table) {
                $table->dropColumn('daily_draw_limit');
            });
        }
    }
};
