<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShopServiceTable extends Migration
{
    public function up()
    {
        Schema::create('shop_service', function (Blueprint $table) {
            $table->increments('service_id')->comment('客服設置id');
            $table->string('service_url', 255)->nullable()->comment('客服鏈接');
            $table->string('service_account', 255)->nullable()->comment('客服設置賬號');
            $table->integer('service_status')->nullable()->default(1)->comment('状态(1可用，0禁用,-1删除)');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('shop_service');
    }
}
