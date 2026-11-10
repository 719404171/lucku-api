<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpecsValueTable extends Migration
{
    public function up()
    {
        Schema::create('specs_value', function (Blueprint $table) {
            $table->increments('specs_value_id');
            $table->integer('specs_key_id')->nullable()->comment('規格名id');
            $table->string('specs_value_name', 255)->nullable()->comment('規格值');
            $table->string('en_specs_value_name', 255)->nullable()->comment('規格值（英）');
        });
    }

    public function down()
    {
        Schema::dropIfExists('specs_value');
    }
}
