<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCardSeriesTable extends Migration
{
    public function up()
    {
        Schema::create('card_series', function (Blueprint $table) {
            $table->increments('card_series_id');
            $table->string('card_series_name', 255)->nullable()->comment('產品系列名');
            $table->string('en_card_series_name', 255)->nullable()->comment('產品系列名（英）');
            $table->string('card_series_img', 255)->nullable()->comment('系列圖片');
            $table->integer('status')->nullable()->default(1);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->string('sort', 255)->nullable()->comment('排序');
        });
    }

    public function down()
    {
        Schema::dropIfExists('card_series');
    }
}
