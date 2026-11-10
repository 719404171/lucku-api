<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserCollectTable extends Migration
{
    public function up()
    {
        Schema::create('user_collect', function (Blueprint $table) {
            $table->increments('collect_id')->comment('收藏id');
            $table->integer('user_id')->nullable()->comment('用戶id');
            $table->integer('product_id')->nullable()->comment('商品id');
            $table->string('collect_type', 255)->nullable()->default(1)->comment('收藏類型（1產品  2服务）');
            $table->integer('collect_status')->nullable()->default(1)->comment('状态(1可用，2禁用，-1删除)');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_collect');
    }
}
