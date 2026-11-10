<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 充值檔位優惠金幣領取記錄：每個用戶、每個檔位（exchange_settings）僅首次享受一次。
 * (user_id, exchange_id) 唯一索引作為「只能領一次」的原子保證：
 * 同一用戶並發支付同一檔位時只有一筆能寫入成功，其餘不再贈送優惠金幣。
 */
return new class extends Migration {

    public function up(): void
    {
        if (Schema::hasTable('user_recharge_bonus')) {
            return;
        }
        Schema::create('user_recharge_bonus', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id')->default(0)->comment('用戶ID');
            $table->integer('exchange_id')->default(0)->comment('兌換檔位ID');
            $table->integer('bonus_coin')->default(0)->comment('首次贈送的優惠金幣');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unique(['user_id', 'exchange_id'], 'uniq_urb_user_exchange');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_recharge_bonus');
    }
};
