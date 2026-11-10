<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePowerMenuTable extends Migration
{
    public function up()
    {
        Schema::create('power_menu', function (Blueprint $table) {
            $table->increments('power_menu_id');
            $table->integer('power_id')->nullable()->comment('权限id');
            $table->integer('role_id')->nullable()->comment('角色id');
            $table->integer('menu_id')->nullable()->comment('路由id');
            $table->integer('status')->nullable()->default(0)->comment('0:禁用1:正常');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('power_menu');
    }
}
