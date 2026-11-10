<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 卡池：每用戶抽取次數限制
 * 0 或 NULL = 無限制（預設）；>0 時每用戶最多抽 N 張
 */
return new class extends Migration {

    public function up(): void
    {
        if (Schema::hasColumn('card_sort', 'draw_limit_per_user')) {
            return;
        }
        Schema::table('card_sort', function (Blueprint $table) {
            $table->integer('draw_limit_per_user')->default(0)->after('card_draw_price')
                ->comment('每用戶抽取次數限制：0=無限制，>0=每用戶最多抽 N 張');
        });
    }

    public function down(): void
    {
        Schema::table('card_sort', function (Blueprint $table) {
            $table->dropColumn('draw_limit_per_user');
        });
    }
};
