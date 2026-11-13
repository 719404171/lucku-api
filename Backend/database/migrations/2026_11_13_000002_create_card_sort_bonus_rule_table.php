<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

/**
 * 卡池額外獎勵規則：每 N 張發一張指定獎勵卡
 * 例：trigger_at = 10 表示每 10 張發一次（第 10、20、30... 張）
 */
class CreateCardSortBonusRuleTable extends Migration
{
    public function up()
    {
        Schema::create('card_sort_bonus_rule', function (Blueprint $table) {
            $table->bigIncrements('rule_id');
            $table->integer('card_sort_id')->default(0)->comment('卡池ID');
            $table->integer('trigger_at')->default(0)->comment('每N張發一次');
            $table->integer('card_id')->default(0)->comment('獎勵卡牌ID（必須屬於本池）');
            $table->tinyInteger('is_active')->default(1)->comment('1=啟用 0=停用');
            $table->integer('sort')->default(0)->comment('排序（後台展示用）');
            $table->timestamps();

            $table->unique(['card_sort_id', 'trigger_at'], 'uniq_csbr_sort_trigger');
            $table->index(['card_sort_id', 'is_active'], 'idx_csbr_sort_active');
        });
    }

    public function down()
    {
        Schema::dropIfExists('card_sort_bonus_rule');
    }
}
