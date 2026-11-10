<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNewsTable extends Migration
{
    public function up()
    {
        Schema::create('news', function (Blueprint $table) {
            $table->increments('news_id');
            $table->string('news_title', 255)->nullable()->comment('最新資訊標題');
            $table->string('en_news_title', 255)->nullable()->comment('最新資訊標題(英文)');
            $table->string('news_img', 255)->nullable()->comment('新聞資訊圖片');
            $table->text('news_text')->nullable()->comment('最新資訊內容');
            $table->text('en_news_text')->nullable()->comment('最新資訊內容(英文)');
            $table->integer('reading_num')->default(0)->comment('閱讀數量');
            $table->integer('news_status')->nullable()->default(1)->comment('状态(0禁用，1可用，-1删除)');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('news');
    }
}
