<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActivityLogTable extends Migration
{
    public function up()
    {
        Schema::create('activity_log', function (Blueprint $table) {
            $table->increments('activity_id')->comment('員工操作日誌id');
            $table->integer('admin_id')->nullable()->comment('管理员id');
            $table->string('admin_name', 255)->nullable()->comment('管理员名称');
            $table->integer('activity_type')->default(1)->comment('操作类型： 1：用户积分  2：用户钱包3:庫存4：位置');
            $table->integer('activity_mode')->default(1)->comment('操作方式： 1：新增  2：减少');
            $table->integer('activity_number')->default(0)->comment('操作数量');
            $table->string('en_activity_value', 255)->nullable()->comment('員工操作日誌修改信息');
            $table->string('activity_value', 255)->nullable()->comment('員工操作日誌修改信息');
            $table->integer('order_id')->nullable()->comment('訂單id');
            $table->integer('activity_status')->default(1)->comment('1 可用 2禁用  -1删除');
            $table->dateTime('created_at')->nullable()->comment('創建時間');
            $table->dateTime('updated_at')->nullable()->comment('修改時間');
        });
    }

    public function down()
    {
        Schema::dropIfExists('activity_log');
    }
}
