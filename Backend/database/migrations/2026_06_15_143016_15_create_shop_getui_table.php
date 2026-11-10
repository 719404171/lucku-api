<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShopGetuiTable extends Migration
{
    public function up()
    {
        Schema::create('shop_getui', function (Blueprint $table) {
            $table->increments('getui_id')->comment('个推id');
            $table->string('app_id', 150)->nullable()->comment('AppID');
            $table->string('app_key', 150)->nullable()->comment('AppKey');
            $table->string('app_secret', 150)->nullable()->comment('AppSecret');
            $table->string('master_secret', 150)->nullable()->comment('MasterSecret');
            $table->string('host', 150)->nullable()->comment('HOST');
            $table->integer('getui_status')->default(1)->comment('1 可用 0禁用  -1删除');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('shop_getui');
    }
}
