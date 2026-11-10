<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderTable extends Migration
{
    public function up()
    {
        Schema::create('order', function (Blueprint $table) {
            $table->increments('order_id')->comment('訂單ID');
            $table->integer('user_id')->nullable()->comment('用戶id');
            $table->string('coupon_id', 255)->nullable()->comment('優惠券id');
            $table->string('order_no', 255)->nullable()->comment('訂單號');
            $table->decimal('original_cost', 10, 2)->default(0.00)->comment('訂單原价=订单实际支付金额+订单优惠金额');
            $table->decimal('order_money', 10, 2)->default(0.00)->comment('订单实际支付金额');
            $table->decimal('coupon_money', 10, 2)->comment('订单优惠金额');
            $table->decimal('freight_price', 10, 2)->default(0.00)->comment('订单运费');
            $table->decimal('booking_price', 10, 2)->default(0.00)->comment('预约到店金额');
            $table->string('delivery_address', 255)->nullable()->comment('收货地址-地址');
            $table->string('delivery_area', 255)->nullable()->comment('收货地址-区域');
            $table->string('delivery_name', 255)->nullable()->comment('收货地址-姓名');
            $table->string('delivery_phone', 255)->nullable()->comment('收货地址-电话');
            $table->string('delivery_mail', 255)->nullable()->comment('收货地址-邮件');
            $table->integer('payment_type')->default(1)->comment('支付类型：1：立即支付，2：預約到店查看');
            $table->integer('payment_mode')->default(1)->comment('支付方式 1：paypal  2：Stripe，3：錢包 ，4：其他支付（上傳支付圖片）5：線下支付');
            $table->integer('order_type')->default(1)->comment('訂單類型：1：線上訂單，2：線下訂單');
            $table->integer('indent_type')->default(1)->comment('訂單類型  1產品訂單  2預約訂單');
            $table->integer('receiving_type')->default(1)->comment('取货方式：1：商家发货，2：自取');
            $table->string('payment_image', 255)->nullable()->comment('訂單支付圖片（其他支付）');
            $table->string('order_time', 100)->nullable()->comment('訂單预约时间');
            $table->integer('order_num')->default(1)->comment('訂單预约人数');
            $table->string('refund_id', 255)->nullable()->comment('訂單退款id');
            $table->integer('payment_status')->default(1)->comment('支付状态：1：待支付   3：已支付 5：已完成 6: 已评论');
            $table->integer('return_status')->default(0)->comment('退款状态：0：不可退款  1：可退款  2：退款中  3：已退款');
            $table->integer('push_state')->default(1)->comment('推送状态：1：未推送 2：已推送');
            $table->integer('mail_state')->default(1)->comment('郵件發送狀態 ： 1：未發送  2：已發送');
            $table->integer('order_status')->default(1)->comment('状态(1可用，0禁用,-1删除)');
            $table->dateTime('payment_at')->nullable()->comment('支付成功時間');
            $table->string('order_remark', 255)->nullable()->comment('訂單备注');
            $table->dateTime('created_at')->nullable()->comment('訂單創建時間');
            $table->dateTime('updated_at')->nullable()->comment('訂單更新時間');
            $table->string('order_code', 255)->nullable()->comment('订单二维码');
        });
    }

    public function down()
    {
        Schema::dropIfExists('order');
    }
}
