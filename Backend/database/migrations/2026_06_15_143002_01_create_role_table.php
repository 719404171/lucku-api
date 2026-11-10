<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoleTable extends Migration
{
    public function up()
    {
        Schema::create('role', function (Blueprint $table) {
            $table->increments('role_id');
            $table->string('role_name', 255)->nullable()->comment('角色名');
            $table->string('en_role_name', 255)->nullable()->comment('角色名（英）');
        });
    }

    public function down()
    {
        Schema::dropIfExists('role');
    }
}
