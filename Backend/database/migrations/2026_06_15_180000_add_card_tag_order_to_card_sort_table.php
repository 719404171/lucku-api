<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCardTagOrderToCardSortTable extends Migration
{
    public function up()
    {
        Schema::table('card_sort', function (Blueprint $table) {
            $table->text('card_tag_order')->nullable()->comment('標籤順序陣列 JSON')->after('status');
        });
    }

    public function down()
    {
        Schema::table('card_sort', function (Blueprint $table) {
            $table->dropColumn('card_tag_order');
        });
    }
}
