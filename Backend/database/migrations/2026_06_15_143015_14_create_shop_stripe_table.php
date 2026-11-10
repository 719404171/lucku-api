<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShopStripeTable extends Migration
{
    public function up()
    {
        Schema::create('shop_stripe', function (Blueprint $table) {
            $table->increments('stripe_id')->comment('STRIPE支付id');
            $table->string('stripe_key', 255)->nullable()->comment('STRIPE支付账号');
            $table->string('stripe_secret', 255)->nullable()->comment('STRIPE支付秘钥');
            $table->integer('stripe_enable')->default(0)->comment('是否启用  1是  0关闭 （只能一个启用，其他就得禁用）');
            $table->integer('stripe_status')->nullable()->default(1)->comment('状态(1可用，0禁用,-1删除)');
            $table->integer('stripe_env')->default(1)->comment('环境  1：live 2：sandbox');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('shop_stripe');
    }
}
