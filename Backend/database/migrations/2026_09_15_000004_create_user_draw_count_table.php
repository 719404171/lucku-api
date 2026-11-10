<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 用戶抽取次數統計：記錄「每個用戶對每個卡池已抽多少張」
 * (user_id, card_sort_id) 唯一索引，抽卡時用 INSERT ... ON DUPLICATE KEY UPDATE 原子 +N
 */
return new class extends Migration {

    public function up(): void
    {
        if (Schema::hasTable('user_draw_count')) {
            return;
        }
        Schema::create('user_draw_count', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id')->default(0)->comment('用戶ID');
            $table->integer('card_sort_id')->default(0)->comment('卡池ID');
            $table->integer('draw_count')->default(0)->comment('已抽取張數');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->unique(['user_id', 'card_sort_id'], 'uniq_udc_user_sort');
            $table->index(['card_sort_id'], 'idx_udc_sort');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_draw_count');
    }
};
