<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTradingCardsTable extends Migration
{
    public function up()
    {
        Schema::create('trading_cards', function (Blueprint $table) {
            $table->increments('id');
            $table->string('url', 512)->nullable()->default('')->comment('来源URL');
            $table->string('title', 512)->nullable()->default('')->comment('标题');
            $table->string('card_name', 512)->nullable()->default('')->comment('卡片名称');
            $table->string('brand', 256)->nullable()->default('')->comment('品牌');
            $table->string('category', 256)->nullable()->default('')->comment('品类');
            $table->decimal('min_price', 12, 2)->nullable()->comment('最低价');
            $table->string('min_price_currency', 32)->nullable()->default('')->comment('最低价货币');
            $table->string('min_price_format', 64)->nullable()->default('')->comment('最低价格式');
            $table->integer('listing_count')->nullable()->default(0)->comment('列表数量');
            $table->integer('offer_count')->nullable()->default(0)->comment('报价数量');
            $table->integer('used_listing_count')->nullable()->default(0)->comment('二手列表数量');
            $table->string('used_listing_count_text', 64)->nullable()->default('')->comment('二手列表数量文本');
            $table->decimal('used_min_price', 12, 2)->nullable()->comment('二手最低价');
            $table->string('used_min_price_currency', 32)->nullable()->default('')->comment('二手最低价货币');
            $table->string('used_min_price_format', 64)->nullable()->default('')->comment('二手最低价格式');
            $table->string('max_price', 64)->nullable()->default('')->comment('最高价');
            $table->decimal('max_price_amount', 12, 2)->nullable()->comment('最高价金额');
            $table->string('max_price_currency', 32)->nullable()->default('')->comment('最高价货币');
            $table->string('max_price_format', 64)->nullable()->default('')->comment('最高价格式');
            $table->string('release_date', 64)->nullable()->default('')->comment('发布日期');
            $table->string('product_code', 128)->nullable()->default('')->comment('产品编码');
            $table->string('thumbnail_url', 512)->nullable()->default('')->comment('缩略图URL');
            $table->string('card_type', 64)->nullable()->default('')->comment('卡片类型');
            $table->text('description')->nullable()->comment('描述');
            $table->integer('image_count')->nullable()->default(0)->comment('图片数量');
            $table->longText('condition_prices')->nullable()->comment('全部成色价格（JSON）');
            $table->dateTime('created_at')->nullable()->useCurrent()->comment('创建时间');
            $table->dateTime('updated_at')->nullable()->useCurrent()->comment('更新时间');
        });
    }

    public function down()
    {
        Schema::dropIfExists('trading_cards');
    }
}
