<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTradingCardIdToCardTable extends Migration
{
    public function up()
    {
        Schema::table('card', function (Blueprint $table) {
            $table->integer('trading_card_id')->nullable()->default(null)->comment('關聯 trading_cards 表 ID')->after('card_series_name');
        });
    }

    public function down()
    {
        Schema::table('card', function (Blueprint $table) {
            $table->dropColumn('trading_card_id');
        });
    }
}
