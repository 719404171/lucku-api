<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderCommentTable extends Migration
{
    public function up()
    {
        Schema::create('order_comment', function (Blueprint $table) {
            $table->increments('comment_id')->comment('評論id');
            $table->integer('user_id')->nullable()->comment('用戶id');
            $table->integer('product_id')->nullable()->comment('商品id');
            $table->integer('details_id')->nullable()->comment('訂單詳情id');
            $table->string('comment_img', 255)->nullable()->comment('評論圖片');
            $table->string('comment_text', 255)->nullable()->comment('評論內容');
            $table->integer('comment_score')->nullable()->comment('評分');
            $table->integer('comment_status')->default(1)->comment('状态(1可用，0禁用，-1删除)');
            $table->dateTime('created_at')->nullable()->comment('創建時間');
            $table->dateTime('updated_at')->nullable()->comment('修改時間');
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_comment');
    }
}
