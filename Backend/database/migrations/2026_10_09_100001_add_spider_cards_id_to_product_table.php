<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddSpiderCardsIdToProductTable extends Migration
{
    public function up()
    {
        Schema::table('product', function (Blueprint $table) {
            if (!Schema::hasColumn('product', 'spider_cards_id')) {
                $table->integer('spider_cards_id')->nullable()->default(null)->comment('關聯 spider_cards 表 ID')->after('trading_card_id');
            }
        });

        // 補全存量數據：優先用 trading_card_id 直接匹配，其次用 crawler_id 匹配
        DB::statement('UPDATE product INNER JOIN spider_cards ON spider_cards.id = product.trading_card_id SET product.spider_cards_id = spider_cards.id WHERE product.spider_cards_id IS NULL');
        DB::statement("UPDATE product INNER JOIN spider_cards ON spider_cards.crawler_id = product.crawler_id SET product.spider_cards_id = spider_cards.id WHERE product.spider_cards_id IS NULL AND product.crawler_id IS NOT NULL AND product.crawler_id <> ''");
    }

    public function down()
    {
        Schema::table('product', function (Blueprint $table) {
            if (Schema::hasColumn('product', 'spider_cards_id')) {
                $table->dropColumn('spider_cards_id');
            }
        });
    }
}
