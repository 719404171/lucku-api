<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCardTagToCardTable extends Migration
{
    public function up()
    {
        Schema::table('card', function (Blueprint $table) {
            $table->string('card_tag', 128)->nullable()->default('預設')->comment('卡片標籤分組')->after('trading_card_id');
        });
    }

    public function down()
    {
        Schema::table('card', function (Blueprint $table) {
            $table->dropColumn('card_tag');
        });
    }
}
