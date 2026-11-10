<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSystemInfoTable extends Migration
{
    public function up()
    {
        Schema::create('system_info', function (Blueprint $table) {
            $table->increments('system_id')->comment('系統消息id');
            $table->integer('user_id')->nullable()->comment('用戶id');
            $table->integer('order_id')->nullable()->comment('訂單id');
            $table->integer('system_type')->nullable()->default(1)->comment('消息類型  1产品  2预约 3系統消息');
            $table->text('system_content')->nullable()->comment('系統消息內容');
            $table->text('en_system_content')->nullable()->comment('系統消息內容(英文)');
            $table->integer('is_read')->nullable()->default(1)->comment('是否已讀  1未讀  2已讀');
            $table->integer('system_status')->default(1)->comment('1 可用 2禁用  -1删除');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('system_info');
    }
}
