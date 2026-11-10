<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCardSortTable extends Migration
{
    public function up()
    {
        Schema::create('card_sort', function (Blueprint $table) {
            $table->increments('card_sort_id')->comment('卡牌分类ID');
            $table->integer('card_series_id')->nullable()->comment('卡牌系列id');
            $table->string('card_sort_name', 255)->nullable()->comment('卡牌分類名');
            $table->string('en_card_sort_name', 255)->nullable()->comment('卡牌分類名（英）');
            $table->string('card_sort_img', 255)->nullable()->comment('分類圖片');
            $table->integer('status')->nullable()->default(1);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->text('card_sort_introduce')->nullable()->comment('卡牌分類介绍');
            $table->text('en_card_sort_introduce')->nullable()->comment('卡牌分類介绍（英）');
            $table->string('card_series_name', 255)->nullable()->comment('卡牌系列名称');
            $table->decimal('card_draw_price', 10, 2)->nullable()->comment('卡牌抽卡价格');
            $table->string('card_sort_url', 255)->nullable()->comment('卡牌过程动画');
        });
    }

    public function down()
    {
        Schema::dropIfExists('card_sort');
    }
}
