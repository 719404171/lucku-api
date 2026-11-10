<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShopFaqTable extends Migration
{
    public function up()
    {
        Schema::create('shop_faq', function (Blueprint $table) {
            $table->increments('faq_id')->comment('問答系統id');
            $table->string('faq_title', 255)->nullable()->comment('問答標題');
            $table->string('en_faq_title', 255)->nullable()->comment('問答標題(英)');
            $table->text('faq_result')->nullable()->comment('問答答案');
            $table->text('en_faq_result')->nullable()->comment('問答答案(英)');
            $table->integer('faq_status')->nullable()->default(1)->comment('状态(1可用，0禁用,-1删除)');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('shop_faq');
    }
}
