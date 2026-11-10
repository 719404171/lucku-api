<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCardRecycleToCardTable extends Migration
{
    public function up()
    {
        Schema::table('card', function (Blueprint $table) {
            if (!Schema::hasColumn('card', 'card_recycle')) {
                $table->decimal('card_recycle', 10, 2)->default(0.00)->comment('卡片回收價格')->after('card_cost');
            }
        });
    }

    public function down()
    {
        Schema::table('card', function (Blueprint $table) {
            $table->dropColumn('card_recycle');
        });
    }
}
