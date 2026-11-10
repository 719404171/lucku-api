<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCheckInTable extends Migration
{
    public function up()
    {
        Schema::create('check_in', function (Blueprint $table) {
            $table->increments('check_id');
            $table->integer('user_id')->comment('用户id');
            $table->date('check_date')->nullable()->comment('签到日期');
            $table->dateTime('created_at')->nullable()->comment('签到时间');
        });
    }

    public function down()
    {
        Schema::dropIfExists('check_in');
    }
}
