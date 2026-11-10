<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFileSortTable extends Migration
{
    public function up()
    {
        Schema::create('file_sort', function (Blueprint $table) {
            $table->increments('file_sort_id');
            $table->string('file_sort_name', 255)->nullable()->comment('文件分类名');
            $table->string('en_file_sort_name', 255)->nullable()->comment('文件分类名（英）');
        });
    }

    public function down()
    {
        Schema::dropIfExists('file_sort');
    }
}
