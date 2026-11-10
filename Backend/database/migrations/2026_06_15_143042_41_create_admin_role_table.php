<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdminRoleTable extends Migration
{
    public function up()
    {
        Schema::create('admin_role', function (Blueprint $table) {
            $table->increments('admin_role_id');
            $table->integer('admin_id')->nullable()->comment('管理员id');
            $table->integer('role_id')->nullable()->comment('角色id');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('admin_role');
    }
}
