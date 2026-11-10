<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMenuTable extends Migration
{
    public function up()
    {
        Schema::create('menu', function (Blueprint $table) {
            $table->increments('menu_id');
            $table->string('menu_name', 255)->nullable()->comment('菜单名');
            $table->string('en_menu_name', 255)->nullable()->comment('菜單名（英）');
            $table->integer('parent')->nullable()->default(0)->comment('父级');
            $table->string('icon', 255)->nullable()->comment('图标');
            $table->string('path', 255)->nullable()->comment('路径');
            $table->string('redirect', 255)->nullable();
            $table->integer('status')->nullable()->default(1)->comment('状态(2禁用，1可用，-1删除)');
            $table->dateTime('created_at')->nullable()->comment('创建时间');
            $table->dateTime('updated_at')->nullable()->comment('修改时间');
        });
    }

    public function down()
    {
        Schema::dropIfExists('menu');
    }
}
