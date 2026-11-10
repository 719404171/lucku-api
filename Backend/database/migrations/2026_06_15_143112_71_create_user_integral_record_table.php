<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserIntegralRecordTable extends Migration
{
    public function up()
    {
        Schema::create('user_integral_record', function (Blueprint $table) {
            $table->increments('integral_id')->comment('用戶積分明細id');
            $table->integer('user_id')->nullable()->comment('用戶id');
            $table->string('integral_type', 255)->nullable()->comment(' 1：增加2：减少');
            $table->string('integral_explain', 255)->nullable()->default('')->comment('说明');
            $table->string('en_integral_explain', 255)->nullable()->comment('说明（英文）');
            $table->string('integral_number', 255)->nullable()->comment('积分数');
            $table->integer('integral_status')->default(1)->comment('1：正常  2：禁用  -1：删除');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_integral_record');
    }
}
