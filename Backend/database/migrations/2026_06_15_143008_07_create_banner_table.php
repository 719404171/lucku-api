<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBannerTable extends Migration
{
    public function up()
    {
        Schema::create('banner', function (Blueprint $table) {
            $table->increments('banner_id')->comment('bannerID');
            $table->string('banner_title', 255)->nullable()->comment('banner標題');
            $table->string('en_banner_title', 255)->nullable();
            $table->string('banner_img', 255)->nullable()->comment('图片路径');
            $table->integer('banner_status')->default(1)->comment('状态(1可用，0禁用，-1删除)');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('banner');
    }
}
