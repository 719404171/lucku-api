<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

/**
 * 卡池額外獎勵規則：支持從卡庫(spider_cards)選獎勵卡
 * - 新增 spider_card_id 列（配置時選卡庫卡）
 * - 保留 card_id（向後兼容舊規則）
 * - 觸發邏輯改為「僅在第 N 張觸發一次」（原為每 N 張循環）
 */
class UpdateBonusRuleForSpiderCard extends Migration
{
    public function up()
    {
        if (Schema::hasTable('card_sort_bonus_rule')) {
            Schema::table('card_sort_bonus_rule', function (Blueprint $table) {
                if (!Schema::hasColumn('card_sort_bonus_rule', 'spider_card_id')) {
                    $table->integer('spider_card_id')->default(0)->after('card_id')
                        ->comment('獎勵卡庫卡牌ID（優先使用，0 時回退用 card_id）');
                }
            });
        }
        if (Schema::hasTable('card_sort_bonus_log')) {
            Schema::table('card_sort_bonus_log', function (Blueprint $table) {
                if (!Schema::hasColumn('card_sort_bonus_log', 'spider_card_id')) {
                    $table->integer('spider_card_id')->default(0)->after('card_id')
                        ->comment('實際發放的卡庫卡牌ID');
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('card_sort_bonus_log')) {
            Schema::table('card_sort_bonus_log', function (Blueprint $table) {
                if (Schema::hasColumn('card_sort_bonus_log', 'spider_card_id')) {
                    $table->dropColumn('spider_card_id');
                }
            });
        }
        if (Schema::hasTable('card_sort_bonus_rule')) {
            Schema::table('card_sort_bonus_rule', function (Blueprint $table) {
                if (Schema::hasColumn('card_sort_bonus_rule', 'spider_card_id')) {
                    $table->dropColumn('spider_card_id');
                }
            });
        }
    }
}
