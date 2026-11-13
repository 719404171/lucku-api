<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

/**
 * 額外獎勵發放記錄（實現全局唯一發放 + 審計追溯）
 * (rule_id, trigger_position) 唯一鍵確保第 N 張、第 2N 張... 各觸發點只發一次
 */
class CreateCardSortBonusLogTable extends Migration
{
    public function up()
    {
        Schema::create('card_sort_bonus_log', function (Blueprint $table) {
            $table->bigIncrements('log_id');
            $table->integer('card_sort_id')->default(0)->comment('卡池ID');
            $table->bigInteger('rule_id')->default(0)->comment('規則ID');
            $table->bigInteger('trigger_position')->default(0)->comment('第幾張觸發（trigger_at 的倍數）');
            $table->bigInteger('user_id')->default(0)->comment('領取用戶');
            $table->integer('card_id')->default(0)->comment('獎勵卡牌ID');
            $table->bigInteger('draw_task_id')->default(0)->comment('對應抽卡批次');
            $table->string('remark', 255)->default('')->comment('備註（庫存不足跳過 / 正常發放）');
            $table->timestamps();

            $table->unique(['rule_id', 'trigger_position'], 'uniq_csbl_rule_pos');
            $table->index(['card_sort_id'], 'idx_csbl_sort');
            $table->index(['user_id'], 'idx_csbl_user');
        });
    }

    public function down()
    {
        Schema::dropIfExists('card_sort_bonus_log');
    }
}
