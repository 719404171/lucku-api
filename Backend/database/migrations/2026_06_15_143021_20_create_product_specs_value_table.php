<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductSpecsValueTable extends Migration
{
    public function up()
    {
        Schema::create('product_specs_value', function (Blueprint $table) {
            $table->increments('product_specs_value_id');
            $table->integer('product_specs_key_id')->nullable()->comment('產品規格名id');
            $table->string('product_specs_value', 255)->nullable()->comment('產品規格值名');
            $table->string('en_product_specs_value', 255)->nullable()->comment('產品規格值名（英）');
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_specs_value');
    }
}
