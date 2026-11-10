<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdminLogTable extends Migration
{
    public function up()
    {
        Schema::create('admin_log', function (Blueprint $table) {
            $table->increments('login_id');
            $table->string('login_admin_id', 255)->nullable()->comment('登錄管理員id');
            $table->string('login_admin_name', 255)->nullable()->comment('登錄管理員姓名');
            $table->string('login_ip', 255)->nullable()->comment('登录IP');
            $table->string('login_nation', 255)->nullable()->comment('登錄國家');
            $table->string('login_region', 255)->nullable()->comment('地區');
            $table->string('login_city', 255)->nullable()->comment('城市');
            $table->string('login_lng', 255)->nullable()->comment('登錄經度');
            $table->string('login_lat', 255)->nullable()->comment('登錄緯度');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('admin_log');
    }
}
