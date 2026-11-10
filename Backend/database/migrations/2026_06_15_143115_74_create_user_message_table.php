<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserMessageTable extends Migration
{
    public function up()
    {
        Schema::create('user_message', function (Blueprint $table) {
            $table->increments('message_id');
            $table->integer('user_id')->nullable()->comment('用户id');
            $table->string('message_content', 255)->nullable()->comment('留言内容');
            $table->integer('message_status')->default(1)->comment('1：正常-1：删除');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_message');
    }
}
