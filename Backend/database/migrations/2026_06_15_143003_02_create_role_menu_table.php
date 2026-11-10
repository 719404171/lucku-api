<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoleMenuTable extends Migration
{
    public function up()
    {
        Schema::create('role_menu', function (Blueprint $table) {
            $table->increments('role_menu_id');
            $table->integer('role_id')->nullable()->comment('角色id');
            $table->integer('menu_id')->nullable()->comment('路由id');
            $table->integer('status')->nullable()->default(0)->comment('0:禁用1:正常');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('role_menu');
    }
}
