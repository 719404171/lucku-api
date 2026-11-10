<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * payment_recharge_log 新增 paid_amount 欄位：記錄用戶實際支付的金額（法幣）
 * 與原有 recharge_money（到帳金幣數）對應，後台充值記錄列表需同時顯示
 */
return new class extends Migration {
    public function up(): void
    {
        try {
            Schema::table('payment_recharge_log', function (Blueprint $table) {
                $table->decimal('paid_amount', 10, 2)->default(0)->after('trade_no')->comment('用戶實際支付金額（法幣）');
                $table->integer('bonus_coin')->default(0)->after('paid_amount')->comment('首次充值贈送金幣');
            });
        } catch (\Throwable $e) {
            // 欄位已存在時跳過
        }
    }

    public function down(): void
    {
        try {
            Schema::table('payment_recharge_log', function (Blueprint $table) {
                $table->dropColumn(['paid_amount', 'bonus_coin']);
            });
        } catch (\Throwable $e) {
        }
    }
};
