<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

/**
 * card 表加 spider_card_id 列：獎勵卡從卡庫複製進 card 表時標記來源
 */
class AddSpiderCardIdToCardTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('card') && !Schema::hasColumn('card', 'spider_card_id')) {
            Schema::table('card', function (Blueprint $table) {
                $table->integer('spider_card_id')->default(0)->after('card_sort_id')
                    ->comment('來源卡庫卡ID（0=非卡庫來源）');
                $table->index('spider_card_id', 'idx_card_spider_card_id');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('card') && Schema::hasColumn('card', 'spider_card_id')) {
            Schema::table('card', function (Blueprint $table) {
                $table->dropIndex('idx_card_spider_card_id');
                $table->dropColumn('spider_card_id');
            });
        }
    }
}
