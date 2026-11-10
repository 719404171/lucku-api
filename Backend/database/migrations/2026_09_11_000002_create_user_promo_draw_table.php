<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 優惠卡包抽取記錄：記錄「哪個帳號抽過哪個優惠卡包」。
 * (user_id, card_sort_id) 唯一索引作為「每帳號只能抽一次」的原子保證：
 * 併發重複提交時只有一筆能寫入成功，其餘在扣款前就被攔下。
 */
return new class extends Migration {

    public function up(): void
    {
        if (Schema::hasTable('user_promo_draw')) {
            return;
        }
        Schema::create('user_promo_draw', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id')->default(0)->comment('用戶ID');
            $table->integer('card_sort_id')->default(0)->comment('優惠卡池ID');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unique(['user_id', 'card_sort_id'], 'uniq_upd_user_sort');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_promo_draw');
    }
};
