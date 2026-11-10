<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductRelationTable extends Migration
{
    public function up()
    {
        Schema::create('product_relation', function (Blueprint $table) {
            $table->increments('product_relation_id');
            $table->integer('product_id')->nullable()->comment('產品id');
            $table->string('product_specs_value_id', 255)->nullable()->comment('產品規格值id組合');
            $table->decimal('price', 10, 2)->nullable()->comment('價格');
            $table->decimal('cost', 10, 2)->nullable()->comment('成本');
            $table->integer('stock')->nullable()->comment('庫存');
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_relation');
    }
}
