<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTradingCardIdAndSortNameToProductTable extends Migration
{
    public function up()
    {
        Schema::table('product', function (Blueprint $table) {
            if (!Schema::hasColumn('product', 'trading_card_id')) {
                $table->integer('trading_card_id')->nullable()->comment('關聯 trading_cards 表 ID')->after('product_sort_id');
            }
            if (!Schema::hasColumn('product', 'card_sort_name')) {
                $table->string('card_sort_name', 255)->nullable()->comment('卡池名稱')->after('trading_card_id');
            }
        });
    }

    public function down()
    {
        Schema::table('product', function (Blueprint $table) {
            $table->dropColumn(['trading_card_id', 'card_sort_name']);
        });
    }
}
