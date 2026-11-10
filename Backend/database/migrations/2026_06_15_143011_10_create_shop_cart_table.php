<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShopCartTable extends Migration
{
    public function up()
    {
        Schema::create('shop_cart', function (Blueprint $table) {
            $table->increments('cart_id')->comment('購物車id');
            $table->integer('user_id')->nullable()->comment('用户id');
            $table->integer('product_id')->nullable()->comment('商品id');
            $table->integer('product_price')->nullable();
            $table->integer('product_num')->nullable()->comment('数量');
            $table->string('status', 11)->nullable()->default(1)->comment('-1刪除 1可用');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('shop_cart');
    }
}
