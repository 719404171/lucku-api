<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdminsTable extends Migration
{
    public function up()
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->increments('admin_id');
            $table->string('username', 255)->nullable()->comment('用户名');
            $table->string('password', 255)->nullable()->comment('密码');
            $table->string('avatar', 255)->nullable()->comment('頭像');
            $table->string('email', 255)->nullable()->comment('邮箱');
            $table->string('phone', 255)->nullable()->comment('电话');
            $table->string('token', 2048)->nullable()->comment('TOKEN');
            $table->integer('status')->nullable()->default(1)->comment('状态(0禁用，1可用，-1删除)');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('admins');
    }
}
