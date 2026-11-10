<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCardTable extends Migration
{
    public function up()
    {
        Schema::create('card', function (Blueprint $table) {
            $table->increments('card_id')->comment('產品id');
            $table->integer('card_sort_id')->nullable()->comment('APP类别ID');
            $table->string('card_name', 255)->nullable()->comment('卡片名稱');
            $table->string('en_card_name', 255)->nullable()->comment('卡片名稱（英）');
            $table->string('card_img', 255)->nullable()->comment('卡片展示图');
            $table->integer('is_priority')->default(0)->comment('是否推荐 1：是 0：否');
            $table->integer('is_integral')->default(0)->comment('可否获得积分 1：是  0：否');
            $table->integer('integral_num')->default(0)->comment('反馈积分');
            $table->integer('card_type')->default(1)->comment('卡片种类1：產品 2：服務');
            $table->decimal('card_price', 10, 2)->nullable()->default(0.00)->comment('卡片價格');
            $table->integer('card_cost')->default(0)->comment('卡片成本');
            $table->integer('card_stock')->default(1)->comment('卡片庫存');
            $table->integer('browse_number')->nullable()->default(0)->comment('卡片瀏覽量');
            $table->text('card_describe')->nullable()->comment('卡片描述');
            $table->text('en_card_describe')->nullable()->comment('卡片描述（英）');
            $table->integer('card_status')->nullable()->default(1)->comment('1：正常  0：禁用  -1：删除');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->integer('card_weight')->default(99)->comment('卡片權重 权重越大抽取概率越低');
            $table->string('card_grade', 155)->default(3)->comment('卡片等級   1：傳奇   2：珍惜    3：稀有   4：普通');
            $table->integer('card_series_id')->nullable()->comment('卡牌系列ID');
            $table->string('card_sort_name', 255)->nullable();
            $table->string('card_series_name', 255)->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('card');
    }
}
