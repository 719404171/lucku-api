<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShopCouponTable extends Migration
{
    public function up()
    {
        Schema::create('shop_coupon', function (Blueprint $table) {
            $table->increments('coupon_id')->comment('優惠券id');
            $table->string('coupon_title', 255)->nullable()->comment('優惠券名稱');
            $table->integer('admin_id')->nullable()->comment('管理員id');
            $table->decimal('lowest', 10, 2)->default(0.00)->comment('使用优惠券最低价');
            $table->integer('lowlevel')->nullable()->comment('最低使用等级id');
            $table->integer('coupon_type')->nullable()->default(1)->comment('优惠券类型 1 ：立减 2：满减 3：立折 4：满折  ');
            $table->integer('access_type')->nullable()->default(1)->comment('1为 积分兑换 2为手動發送 3為已手動發送 ');
            $table->decimal('coupon_price', 10, 2)->comment('兑换价格');
            $table->string('coupon_discount', 11)->nullable()->default(0)->comment('優惠券折扣');
            $table->integer('coupon_stock')->default(0)->comment('優惠券库存');
            $table->integer('coupon_convert')->default(0)->comment('兑换次数');
            $table->text('coupon_explain')->nullable()->comment('使用說明');
            $table->dateTime('start_time')->nullable()->comment('开始时间');
            $table->dateTime('end_time')->nullable()->comment('结束时间');
            $table->integer('coupon_status')->nullable()->default(1)->comment('状态(0:新发布，1：正常,2：待审批,3：驳回,-1：删除) ');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('shop_coupon');
    }
}
