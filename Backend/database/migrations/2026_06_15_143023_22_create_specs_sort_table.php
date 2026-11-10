<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpecsSortTable extends Migration
{
    public function up()
    {
        Schema::create('specs_sort', function (Blueprint $table) {
            $table->increments('specs_sort_id');
            $table->string('specs_sort_name', 255)->nullable()->comment('規格分類名');
            $table->string('en_specs_sort_name', 255)->nullable()->comment('規格分類名（英）');
            $table->integer('status')->nullable()->default(1);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('specs_sort');
    }
}
