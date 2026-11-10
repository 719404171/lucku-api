<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePushTable extends Migration
{
    public function up()
    {
        Schema::create('push', function (Blueprint $table) {
            $table->increments('push_id');
            $table->integer('admin_id')->nullable()->comment('管理员id');
            $table->integer('user_id')->nullable()->comment('用户id');
            $table->integer('push_type')->nullable()->default(1)->comment('1：单用户2：多用户');
            $table->string('push_title', 255)->nullable()->comment('推送标题');
            $table->string('push_content', 255)->nullable()->comment('推送内容');
            $table->integer('push_platform')->nullable()->default(0)->comment('0：全部1：ios2：安卓');
            $table->integer('push_status')->nullable()->default(1)->comment('1：正常-1：删除');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('push');
    }
}
