<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBannerPopupTable extends Migration
{
    public function up()
    {
        Schema::create('banner_popup', function (Blueprint $table) {
            $table->increments('popup_id')->comment('弹窗ID');
            $table->string('popup_title', 255)->nullable()->comment('弹窗标题');
            $table->string('popup_img', 255)->comment('弹窗图片（仅单张）');
            $table->integer('popup_enable')->default(1)->comment('是否展示(1展示，0隐藏)');
            $table->integer('popup_status')->default(1)->comment('状态(1正常，0禁用，-1删除)');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('banner_popup');
    }
}
