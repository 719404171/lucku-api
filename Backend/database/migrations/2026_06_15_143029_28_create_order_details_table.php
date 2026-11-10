<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderDetailsTable extends Migration
{
    public function up()
    {
        Schema::create('order_details', function (Blueprint $table) {
            $table->increments('details_id')->comment('訂單詳情id');
            $table->integer('order_id')->nullable()->comment('訂單id');
            $table->integer('user_id')->nullable()->comment('用戶id');
            $table->integer('product_id')->nullable()->comment('商品id');
            $table->integer('product_relation_id')->nullable()->comment('產品規格組合ID');
            $table->string('product_specs', 255)->nullable()->comment('產品規格');
            $table->string('product_name', 255)->nullable()->comment('商品名');
            $table->string('en_product_name', 255)->nullable()->comment('商品英文名');
            $table->string('product_img', 255)->nullable()->comment('商品展示图');
            $table->integer('product_number')->nullable()->comment('购买数量');
            $table->decimal('product_price', 10, 2)->nullable()->comment('單價金额');
            $table->decimal('product_money', 10, 2)->nullable()->comment('商品總價');
            $table->integer('product_type')->nullable()->comment('商品种类1：產品 2：預約');
            $table->integer('details_status')->default(1)->comment('状态(1可用，2禁用,-1删除)');
            $table->integer('logistics_status')->default(0)->comment('物流狀態 0：待發貨   1：發貨中   2：已收貨  3:已評論');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->string('details_remark', 255)->nullable()->comment('訂單詳情備註');
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_details');
    }
}
