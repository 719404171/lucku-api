<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserMemberTable extends Migration
{
    public function up()
    {
        Schema::create('user_member', function (Blueprint $table) {
            $table->increments('member_id')->comment('會員等級id');
            $table->string('member_name', 100)->nullable()->comment('等级显示名称');
            $table->string('en_member_name', 100)->nullable()->comment('等级显示名称(英文)');
            $table->string('member_img', 255)->nullable()->comment('等級圖片');
            $table->integer('member_start')->nullable()->comment('范围开始');
            $table->integer('member_end')->nullable()->comment('范围结束');
            $table->integer('member_status')->default(1)->comment('1：正常  2：禁用  -1：删除');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_member');
    }
}
