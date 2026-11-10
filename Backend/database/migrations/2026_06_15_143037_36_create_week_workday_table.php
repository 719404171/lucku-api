<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWeekWorkdayTable extends Migration
{
    public function up()
    {
        Schema::create('week_workday', function (Blueprint $table) {
            $table->increments('weekday_id')->comment('星期id');
            $table->time('start_time')->nullable()->comment('開店時間');
            $table->time('end_time')->nullable()->comment('閉店時間');
            $table->string('weekday', 255)->nullable();
            $table->string('is_open', 255)->nullable()->comment('是否營業0:否 1:是');
            $table->integer('weekday_status')->default(1)->comment('1 可用 2禁用  -1删除');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('week_workday');
    }
}
