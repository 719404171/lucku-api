<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShopPaypalTable extends Migration
{
    public function up()
    {
        Schema::create('shop_paypal', function (Blueprint $table) {
            $table->increments('paypal_id')->comment('PayPal支付id');
            $table->string('client_id', 255)->nullable()->comment('PayPal支付账号');
            $table->string('paypal_secret', 255)->nullable()->comment('PayPal支付秘钥');
            $table->integer('paypal_enable')->nullable()->default(0)->comment('是否启用  1是  0关闭 （只能一个启用，其他就得禁用）');
            $table->integer('paypal_env')->default(2)->comment('PayPal支付环境 1 live  2 sandbox');
            $table->integer('paypal_status')->nullable()->default(1)->comment('状态(1可用，0禁用,-1删除)');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('shop_paypal');
    }
}
