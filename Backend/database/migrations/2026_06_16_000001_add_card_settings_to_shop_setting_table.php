<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCardSettingsToShopSettingTable extends Migration
{
    public function up()
    {
        Schema::table('shop_setting', function (Blueprint $table) {
            if (!Schema::hasColumn('shop_setting', 'card_recycle_percent')) {
                $table->integer('card_recycle_percent')->default(0)->comment('卡牌回收百分比');
            }
            if (!Schema::hasColumn('shop_setting', 'card_sell_percent')) {
                $table->integer('card_sell_percent')->default(100)->comment('卡牌售賣百分比');
            }
            if (!Schema::hasColumn('shop_setting', 'card_import_stock')) {
                $table->integer('card_import_stock')->default(1)->comment('卡牌導入預設庫存');
            }
        });
    }

    public function down()
    {
        Schema::table('shop_setting', function (Blueprint $table) {
            $table->dropColumn(['card_recycle_percent', 'card_sell_percent', 'card_import_stock']);
        });
    }
}
