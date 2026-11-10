<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAddressAreaTable extends Migration
{
    public function up()
    {
        Schema::create('address_area', function (Blueprint $table) {
            $table->increments('area_id')->comment('区域选择id');
            $table->string('en_area_name', 255)->nullable()->comment('区域名称-英文');
            $table->string('area_name', 255)->nullable()->comment('区域名称');
            $table->integer('area_parent')->default(0)->comment('區域父級');
            $table->integer('area_status')->nullable()->default(1)->comment('1 可用 0禁用  -1删除');
            $table->dateTime('created_at')->nullable()->comment('用户创建时间');
            $table->dateTime('updated_at')->nullable()->comment('用户更新时间');
        });
    }

    public function down()
    {
        Schema::dropIfExists('address_area');
    }
}
