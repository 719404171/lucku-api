<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class AddCardFieldsToCardTable extends Migration
{
    public function up()
    {
        Schema::table('card', function (Blueprint $table) {
            $table->string('crawler_id', 255)->nullable()->comment('爬蟲來源 ID（來自 spider_cards）')->after('trading_card_id');
            $table->string('crawler_url', 500)->nullable()->comment('爬蟲來源 URL')->after('crawler_id');
            $table->string('card_category', 255)->nullable()->comment('卡牌分類')->after('crawler_url');
            $table->string('deal_condition', 255)->nullable()->comment('成色')->after('card_category');
        });
    }

    public function down()
    {
        Schema::table('card', function (Blueprint $table) {
            $table->dropColumn(['crawler_id', 'crawler_url', 'card_category', 'deal_condition']);
        });
    }
}
