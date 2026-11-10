<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCouponTable extends Migration
{
    public function up()
    {
        Schema::create('coupon', function (Blueprint $table) {
            $table->increments('coupon_id');
            $table->integer('admin_id')->nullable()->comment('管理员id');
            $table->string('coupon_name', 255)->nullable()->comment('优惠券名称');
            $table->string('en_coupon_name', 255)->nullable()->comment('优惠券名称（英）');
            $table->integer('coupon_type')->nullable()->comment('1：現金券 2：現金券 3：折扣券 4：折扣券');
            $table->integer('lowlevel')->nullable()->comment('最低使用等级id');
            $table->decimal('lowest', 10, 2)->nullable()->default(0.00)->comment('使用优惠券最低价');
            $table->string('discount', 255)->nullable()->comment('折扣');
            $table->integer('coupon_stock')->default(0)->comment('优惠券庫存');
            $table->integer('conversion_price')->nullable()->comment('兑换价格');
            $table->dateTime('expiration_time')->nullable()->comment('到期时间');
            $table->string('instructions', 255)->nullable()->comment('使用说明');
            $table->string('en_instructions', 255)->nullable()->comment('使用说明（英）');
            $table->integer('coupon_status')->default(1)->comment('1：正常0：禁用-1：删除');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('coupon');
    }
}
