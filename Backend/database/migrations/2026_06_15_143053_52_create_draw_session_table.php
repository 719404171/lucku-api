<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDrawSessionTable extends Migration
{
    public function up()
    {
        Schema::create('draw_session', function (Blueprint $table) {
            $table->increments('session_id');
            $table->integer('user_id')->nullable()->comment('用戶ID');
            $table->integer('card_sort_id')->nullable()->comment('卡包ID');
            $table->integer('draw_type')->nullable()->comment('抽取類型 1=錢包 2=金幣');
            $table->integer('count')->nullable()->comment('抽取數量');
            $table->decimal('total_cost', 10, 2)->nullable()->comment('花費金額');
            $table->text('draw_data')->nullable()->comment('抽取的卡片數據');
            $table->integer('draw_count')->default(1)->comment('抽取次數');
            $table->integer('status')->nullable()->comment('狀態 0=活躍 1=已確認 2=已取消');
            $table->integer('order_id')->nullable()->comment('確認後的訂單ID');
            $table->dateTime('expires_at')->nullable()->comment('過期時間');
            $table->dateTime('created_at')->nullable()->comment('創建時間');
            $table->dateTime('updated_at')->nullable()->comment('修改時間');
        });
    }

    public function down()
    {
        Schema::dropIfExists('draw_session');
    }
}
