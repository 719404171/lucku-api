<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFileTable extends Migration
{
    public function up()
    {
        Schema::create('file', function (Blueprint $table) {
            $table->increments('file_id');
            $table->integer('file_sort_id')->nullable()->comment('文件分类id');
            $table->string('file_url', 255)->nullable()->comment('文件地址');
        });
    }

    public function down()
    {
        Schema::dropIfExists('file');
    }
}
