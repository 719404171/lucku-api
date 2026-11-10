<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductTable extends Migration
{
    public function up()
    {
        Schema::create('product', function (Blueprint $table) {
            $table->increments('product_id')->comment('產品id');
            $table->integer('product_sort_id')->nullable()->comment('APP类别ID');
            $table->string('product_name', 255)->nullable()->comment('商品名稱');
            $table->string('en_product_name', 255)->nullable()->comment('產品名稱（英）');
            $table->string('product_img', 255)->nullable()->comment('商品展示图');
            $table->integer('is_priority')->nullable()->default(0)->comment('是否推荐 1：是 0：否');
            $table->integer('is_specs')->nullable()->default(0)->comment('是否有规格 1：是  0：否');
            $table->integer('is_album')->nullable()->default(0)->comment('是否有相冊 1：是 0：否');
            $table->integer('is_integral')->nullable()->default(0)->comment('可否获得积分 1：是  0：否');
            $table->integer('is_tag')->nullable()->comment('是否有標籤  1：是 0：否');
            $table->integer('integral_num')->nullable()->default(0)->comment('反馈积分');
            $table->integer('product_type')->nullable()->default(1)->comment('商品种类1：產品 2：服務');
            $table->decimal('product_price', 10, 2)->nullable()->default(0.00)->comment('產品價格');
            $table->integer('product_cost')->default(0)->comment('產品成本');
            $table->integer('product_stock')->nullable()->default(0)->comment('產品庫存');
            $table->integer('browse_number')->nullable()->default(0)->comment('商品瀏覽量');
            $table->text('product_describe')->nullable()->comment('商品描述');
            $table->text('en_product_describe')->nullable()->comment('產品描述（英）');
            $table->string('product_barcode', 255)->nullable()->comment('产品条码');
            $table->string('product_location', 255)->nullable()->comment('產品位置');
            $table->string('product_synopsis', 255)->nullable()->comment('產品簡介');
            $table->string('en_product_synopsis', 255)->nullable()->comment('產品簡介（英）');
            $table->integer('product_hand')->default(0)->comment('產品人手');
            $table->integer('product_status')->nullable()->default(1)->comment('1：正常  0：禁用  -1：删除');
            $table->string('purchase_supplier', 255)->nullable()->comment('產品供應商名稱');
            $table->string('purchase_contact', 255)->nullable()->comment('產品采購聯係方式');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('product');
    }
}
