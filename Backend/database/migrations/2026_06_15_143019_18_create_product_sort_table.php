<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductSortTable extends Migration
{
    public function up()
    {
        Schema::create('product_sort', function (Blueprint $table) {
            $table->increments('product_sort_id');
            $table->string('product_sort_name', 255)->nullable()->comment('產品分類名');
            $table->string('en_product_sort_name', 255)->nullable()->comment('產品分類名（英）');
            $table->string('product_sort_img', 255)->nullable()->comment('分類圖片');
            $table->integer('status')->nullable()->default(1);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_sort');
    }
}
