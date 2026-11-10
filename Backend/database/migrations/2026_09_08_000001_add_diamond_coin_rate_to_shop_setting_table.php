<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDiamondCoinRateToShopSettingTable extends Migration
{
    public function up()
    {
        Schema::table('shop_setting', function (Blueprint $table) {
            if (!Schema::hasColumn('shop_setting', 'diamond_coin_rate')) {
                $table->decimal('diamond_coin_rate', 10, 2)->default(1.00)->comment('鑽石兌換金幣比例（1鑽石 = N金幣）')->after('card_import_stock');
            }
        });
    }

    public function down()
    {
        Schema::table('shop_setting', function (Blueprint $table) {
            if (Schema::hasColumn('shop_setting', 'diamond_coin_rate')) {
                $table->dropColumn('diamond_coin_rate');
            }
        });
    }
}
