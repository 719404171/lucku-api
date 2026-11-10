<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCardBrandToCardTable extends Migration
{
    public function up()
    {
        Schema::table('card', function (Blueprint $table) {
            if (!Schema::hasColumn('card', 'card_brand')) {
                $table->string('card_brand', 256)->nullable()->comment('卡牌品牌')->after('card_name');
            }
        });
    }

    public function down()
    {
        Schema::table('card', function (Blueprint $table) {
            $table->dropColumn('card_brand');
        });
    }
}
