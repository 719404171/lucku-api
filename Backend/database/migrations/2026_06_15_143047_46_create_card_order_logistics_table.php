<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCardOrderLogisticsTable extends Migration
{
    public function up()
    {
        Schema::create('card_order_logistics', function (Blueprint $table) {
            $table->increments('card_order_logistics_id')->comment('訂單物流id');
            $table->integer('card_order_details_id')->nullable()->comment('訂單詳情id');
            $table->integer('user_id')->nullable()->comment('用戶id');
            $table->string('card_order_logistics_number', 255)->nullable()->comment('物流單號');
            $table->integer('logistics_status')->default(0)->comment('物流狀態 0：待發貨   1：發貨中   2：已收貨  3: 已評論');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('card_order_logistics');
    }
}
