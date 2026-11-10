<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserAddressTable extends Migration
{
    public function up()
    {
        Schema::create('user_address', function (Blueprint $table) {
            $table->increments('address_id')->comment('收貨地址id');
            $table->integer('user_id')->nullable()->comment('用戶id');
            $table->string('address_name', 255)->nullable()->comment('收貨姓名');
            $table->string('address_mail', 255)->nullable()->comment('收貨电邮');
            $table->string('address_phone', 255)->nullable()->comment('收貨電話');
            $table->string('address_area', 255)->nullable()->comment('收貨地址区域');
            $table->string('address_address', 255)->nullable()->comment('收貨地址');
            $table->integer('address_default')->default(0)->comment('是否為預設地址 1是 0不是');
            $table->integer('address_status')->default(1)->comment('狀態1：正常 2：禁用-1：删除');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_address');
    }
}
