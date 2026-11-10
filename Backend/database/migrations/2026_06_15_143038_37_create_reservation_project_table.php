<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReservationProjectTable extends Migration
{
    public function up()
    {
        Schema::create('reservation_project', function (Blueprint $table) {
            $table->increments('reservation_project_id')->comment('预约项目id');
            $table->string('reservation_project_name', 255)->nullable()->comment('预约项目名');
            $table->string('en_reservation_project_name', 255)->nullable()->comment('预约项目名（英）');
            $table->string('reservation_project_url', 255)->nullable()->comment('預約項目圖片');
            $table->string('reservation_project_describe', 255)->nullable()->comment('描述');
            $table->string('en_reservation_project_describe', 255)->nullable()->comment('描述（英）');
            $table->integer('reservation_project_stock')->nullable()->comment('每日票数');
            $table->string('reservation_project_badge', 255)->nullable()->comment('项目标识');
            $table->integer('reservation_project_time')->nullable()->comment('项目时长(分钟)');
            $table->integer('reservation_project_status')->default(1)->comment('1：正常0：禁用-1：删除');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('reservation_project');
    }
}
