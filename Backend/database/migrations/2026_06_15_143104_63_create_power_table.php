<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePowerTable extends Migration
{
    public function up()
    {
        Schema::create('power', function (Blueprint $table) {
            $table->increments('power_id');
            $table->string('power_name', 255)->nullable()->comment('权限名');
            $table->string('en_power_name', 255)->nullable()->comment('權限名（英）');
            $table->string('type', 255)->nullable()->comment('权限标识');
        });
    }

    public function down()
    {
        Schema::dropIfExists('power');
    }
}
