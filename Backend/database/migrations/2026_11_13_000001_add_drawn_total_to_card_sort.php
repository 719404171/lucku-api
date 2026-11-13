<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

/**
 * 卡池全局已抽張數（用於「每 N 張額外獎勵」功能的觸發判定）
 * 初始化時按當前池內 card_stock = 0 的卡牌數量回填，保持與後台現有「已抽」數據一致。
 */
class AddDrawnTotalToCardSort extends Migration
{
    public function up()
    {
        if (Schema::hasTable('card_sort') && !Schema::hasColumn('card_sort', 'drawn_total')) {
            Schema::table('card_sort', function (Blueprint $table) {
                $table->bigInteger('drawn_total')->default(0)->after('draw_enable')
                    ->comment('卡池全局累計已抽張數（用於額外獎勵判定）');
            });

            // 回填歷史數據：池內 card_stock = 0 的卡牌數量即為已抽數
            \Illuminate\Support\Facades\DB::statement(
                'UPDATE card_sort cs ' .
                'LEFT JOIN (SELECT card_sort_id, COUNT(*) as drawn FROM card WHERE card_status <> -1 AND card_stock = 0 GROUP BY card_sort_id) c ON c.card_sort_id = cs.card_sort_id ' .
                'SET cs.drawn_total = IFNULL(c.drawn, 0)'
            );
        }
    }

    public function down()
    {
        if (Schema::hasTable('card_sort') && Schema::hasColumn('card_sort', 'drawn_total')) {
            Schema::table('card_sort', function (Blueprint $table) {
                $table->dropColumn('drawn_total');
            });
        }
    }
}
