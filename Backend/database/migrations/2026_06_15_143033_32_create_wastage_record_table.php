<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWastageRecordTable extends Migration
{
    public function up()
    {
        Schema::create('wastage_record', function (Blueprint $table) {
            $table->increments('wastage_id')->comment('損耗記錄id');
            $table->integer('admin_id')->nullable()->comment('管理員id');
            $table->integer('product_id')->nullable()->comment('產品id');
            $table->integer('product_relation_id')->nullable()->comment('產品規格組合id');
            $table->string('product_specs', 255)->nullable()->comment('產品規格');
            $table->integer('wastage_number')->nullable()->comment('損耗數量');
            $table->string('wastage_remarks', 255)->nullable()->comment('損耗備註');
            $table->integer('wastage_status')->default(1)->comment('1：正常  2：禁用  -1：删除');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('wastage_record');
    }
}
