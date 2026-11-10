<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExchangeSettingsTable extends Migration
{
    public function up()
    {
        Schema::create('exchange_settings', function (Blueprint $table) {
            $table->increments('exchange_id')->comment('兌換id');
            $table->decimal('amount', 10, 2)->comment('充值金額');
            $table->integer('exchange_coin')->default(0)->comment('兌換金幣');
            $table->integer('bonus_coin')->default(0)->comment('優惠金幣');
            $table->string('icon', 255)->nullable()->comment('兌換圖標');
            $table->string('description', 255)->nullable()->comment('方案說明');
            $table->integer('sort')->default(0)->comment('排序');
            $table->integer('status')->default(1)->comment('狀態：1啟用 0停用');
            $table->dateTime('created_at')->nullable()->comment('創建時間');
            $table->dateTime('updated_at')->nullable()->comment('修改時間');
        });
    }

    public function down()
    {
        Schema::dropIfExists('exchange_settings');
    }
}
