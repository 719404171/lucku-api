<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductSpecsKeyTable extends Migration
{
    public function up()
    {
        Schema::create('product_specs_key', function (Blueprint $table) {
            $table->increments('product_specs_key_id');
            $table->integer('product_id')->nullable()->comment('產品id');
            $table->string('product_specs_key', 255)->nullable()->comment('產品規格名');
            $table->string('en_product_specs_key', 255)->nullable()->comment('產品規格名（英）');
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_specs_key');
    }
}
