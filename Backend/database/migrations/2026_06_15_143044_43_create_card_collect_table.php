<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCardCollectTable extends Migration
{
    public function up()
    {
        Schema::create('card_collect', function (Blueprint $table) {
            $table->increments('card_collect_id')->comment('收藏id');
            $table->integer('user_id')->nullable()->comment('用戶id');
            $table->integer('card_sort_id')->comment('卡包id');
            $table->string('collect_type', 255)->nullable()->default(1)->comment('收藏類型（1產品  2服务）');
            $table->integer('collect_status')->nullable()->default(1)->comment('状态(1可用，2禁用，-1删除)');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('card_collect');
    }
}
