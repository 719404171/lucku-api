<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductTagTable extends Migration
{
    public function up()
    {
        Schema::create('product_tag', function (Blueprint $table) {
            $table->increments('product_tag_id');
            $table->integer('product_id')->nullable();
            $table->string('product_tag_name', 255)->nullable();
            $table->string('en_product_tag_name', 255)->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_tag');
    }
}
