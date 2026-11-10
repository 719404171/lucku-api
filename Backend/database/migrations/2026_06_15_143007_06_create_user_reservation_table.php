<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserReservationTable extends Migration
{
    public function up()
    {
        Schema::create('user_reservation', function (Blueprint $table) {
            $table->increments('user_reservation_id')->comment('用戶項目票據id');
            $table->integer('reservation_project_id')->nullable()->comment('项目id');
            $table->integer('user_id')->nullable()->comment('用户id');
            $table->string('user_ref', 255)->nullable()->comment('用户编号');
            $table->integer('user_numerical')->nullable()->comment('用戶序號');
            $table->integer('user_status')->nullable()->default(1)->comment('1：排队中2：已签到3：已取消 4：超时');
            $table->date('user_date')->nullable()->comment('排队日期');
            $table->integer('user_reservation_status')->nullable()->default(1)->comment('1：正常-1：删除');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->string('reservation_code', 255)->nullable()->comment('票据二维码');
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_reservation');
    }
}
