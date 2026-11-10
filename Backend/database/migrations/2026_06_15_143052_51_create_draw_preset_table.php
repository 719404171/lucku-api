<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDrawPresetTable extends Migration
{
    public function up()
    {
        Schema::create('draw_preset', function (Blueprint $table) {
            $table->increments('preset_id')->comment('预设抽卡ID');
            $table->integer('card_sort_id')->nullable()->comment('卡包id');
            $table->text('preset_data')->nullable()->comment('預設排序的卡片數據（有序數組）');
            $table->integer('is_active')->default(1)->comment('是否啟用 1=啟用 0=停用');
            $table->integer('is_lock')->default(0)->comment('锁定  0未锁定  1已锁定');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('draw_preset');
    }
}
