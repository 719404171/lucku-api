<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserExchangeTable extends Migration
{
    public function up()
    {
        Schema::create('user_exchange', function (Blueprint $table) {
            $table->increments('exchange_id')->comment('用戶使用優惠券ID');
            $table->integer('user_id')->nullable()->comment('用戶id');
            $table->integer('coupon_id')->nullable()->comment('優惠券id');
            $table->integer('exchange_status')->nullable()->default(0)->comment('状态(1：可使用，2：已使用，-1删除)');
            $table->integer('reminder_status')->nullable()->default(1)->comment('是否提醒過期 1未提醒 2已提醒');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_exchange');
    }
}
