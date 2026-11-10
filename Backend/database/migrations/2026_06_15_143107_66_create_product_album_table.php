<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductAlbumTable extends Migration
{
    public function up()
    {
        Schema::create('product_album', function (Blueprint $table) {
            $table->increments('product_album_id')->comment('產品相冊id');
            $table->integer('product_id')->nullable()->comment('產品id');
            $table->string('image_url', 255)->nullable()->comment('圖片地址');
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_album');
    }
}
