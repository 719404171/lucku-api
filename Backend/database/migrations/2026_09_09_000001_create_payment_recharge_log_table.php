<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        // F-14 充值幂等标记表：trade_no 唯一，防止支付回调/Webhook 重试重复入账
        try {
            Schema::create('payment_recharge_log', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->bigInteger('user_id')->default(0)->index('idx_prl_user');
                $table->string('trade_no', 190)->default('')->comment('支付网关唯一流水号');
                $table->decimal('recharge_money', 10, 2)->default(0);
                $table->tinyInteger('payment_mode')->default(1)->comment('1:paypal 2:stripe 3:wonder');
                $table->tinyInteger('status')->default(1)->comment('1:已入账');
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->unique('trade_no', 'uniq_prl_trade_no');
            });
        } catch (\Throwable $e) {
            // 表已存在等场景直接放過
        }
    }

    public function down(): void
    {
        try {
            Schema::dropIfExists('payment_recharge_log');
        } catch (\Throwable $e) {
            // 忽略
        }
    }
};
