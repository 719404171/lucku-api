<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCardOrderDetailsTable extends Migration
{
    public function up()
    {
        Schema::create('card_order_details', function (Blueprint $table) {
            $table->increments('card_order_details_id')->comment('訂單詳情id');
            $table->integer('card_order_id')->nullable()->comment('訂單id');
            $table->integer('user_id')->nullable()->comment('用戶id');
            $table->integer('card_id')->nullable()->comment('商品id');
            $table->string('card_name', 255)->nullable()->comment('商品名');
            $table->string('en_card_name', 255)->nullable()->comment('商品英文名');
            $table->string('card_img', 255)->nullable()->comment('商品展示图');
            $table->integer('card_number')->nullable()->comment('购买数量');
            $table->decimal('card_price', 10, 2)->nullable()->comment('單價金额');
            $table->decimal('card_money', 10, 2)->nullable()->comment('商品總價');
            $table->integer('card_type')->nullable()->comment('商品种类1：產品 2：預約');
            $table->integer('details_status')->default(1)->comment('状态(1可用，2禁用,-1删除)');
            $table->integer('logistics_status')->default(0)->comment('物流狀態 0：待發貨   1：發貨中   2：已收貨  3:已評論');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->string('details_remark', 255)->nullable()->comment('訂單詳情備註');
            $table->integer('exchange_status')->default(0)->comment('兑换状态： 0未兑换   1已兑换   2发货');
        });
    }

    public function down()
    {
        Schema::dropIfExists('card_order_details');
    }
}
