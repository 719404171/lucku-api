<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 充值記錄表增加 Wonder 網關訂單號（order.number）：
 * 後台看充值記錄時可直接拿該單號到 Wonder 商家後台（hq.wonder.app）搜出對應交易。
 * trade_no 為我方 reference_number，wonder_order_no 為 Wonder 自己的訂單號，兩者互為補充。
 */
return new class extends Migration {

    public function up(): void
    {
        if (!Schema::hasColumn('payment_recharge_log', 'wonder_order_no')) {
            Schema::table('payment_recharge_log', function (Blueprint $table) {
                $table->string('wonder_order_no', 64)->nullable()->default('')->after('trade_no')->comment('Wonder 網關訂單號');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('payment_recharge_log', 'wonder_order_no')) {
            Schema::table('payment_recharge_log', function (Blueprint $table) {
                $table->dropColumn('wonder_order_no');
            });
        }
    }
};
