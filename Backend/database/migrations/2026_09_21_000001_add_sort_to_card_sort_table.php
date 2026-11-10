<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSortToCardSortTable extends Migration
{
    public function up()
    {
        Schema::table('card_sort', function (Blueprint $table) {
            // 如果 sort 字段已存在則跳過（遷移冪等）
            if (!Schema::hasColumn('card_sort', 'sort')) {
                $table->integer('sort')->default(0)->after('card_sort_id')->comment('排序权重，越小越靠前');
            }
        });
    }

    public function down()
    {
        Schema::table('card_sort', function (Blueprint $table) {
            if (Schema::hasColumn('card_sort', 'sort')) {
                $table->dropColumn('sort');
            }
        });
    }
}
