<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchaseRecordTable extends Migration
{
    public function up()
    {
        Schema::create('purchase_record', function (Blueprint $table) {
            $table->increments('purchase_id')->comment('採購記錄id');
            $table->integer('admin_id')->nullable()->comment('管理員id');
            $table->integer('product_id')->nullable()->comment('產品id');
            $table->integer('product_relation_id')->nullable()->comment('產品規格組合id');
            $table->string('product_specs', 255)->nullable()->comment('產品規格');
            $table->string('purchase_supplier', 255)->nullable()->comment('採購供應商');
            $table->string('purchase_contact', 255)->nullable()->comment('採購聯繫方式');
            $table->string('purchase_cost', 10)->nullable()->comment('採購成本');
            $table->integer('purchase_number')->nullable()->comment('採購數量');
            $table->string('purchase_remarks', 255)->nullable()->comment('採購備註');
            $table->integer('purchase_send')->default(0)->comment('郵箱發送 0否  1是');
            $table->integer('purchase_status')->default(1)->comment('1：正常  2：禁用  -1：删除');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->integer('purchase_state')->default(0)->comment('产品状态 0：等待進倉1：進倉、2：退倉、3：轉倉  ');
        });
    }

    public function down()
    {
        Schema::dropIfExists('purchase_record');
    }
}
