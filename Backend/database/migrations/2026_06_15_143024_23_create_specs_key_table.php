<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpecsKeyTable extends Migration
{
    public function up()
    {
        Schema::create('specs_key', function (Blueprint $table) {
            $table->increments('specs_key_id');
            $table->integer('specs_sort_id')->nullable()->comment('規格分類id');
            $table->string('specs_key_name', 255)->nullable()->comment('規格名');
            $table->string('en_specs_key_name', 255)->nullable()->comment('規格名（英）');
        });
    }

    public function down()
    {
        Schema::dropIfExists('specs_key');
    }
}
