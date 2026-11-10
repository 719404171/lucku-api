<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserWalletRecordTable extends Migration
{
    public function up()
    {
        Schema::create('user_wallet_record', function (Blueprint $table) {
            $table->increments('wallet_id')->comment('用戶錢包明細id');
            $table->integer('user_id')->nullable()->comment('用户id');
            $table->integer('wallet_type')->nullable()->comment('1：购买  2：充值 3：提现');
            $table->integer('payment_mode')->default(1)->comment('支付方式 1：paypal  2：Stripe，3：錢包 ，4：其他支付（上傳支付圖片）');
            $table->string('wallet_explain', 255)->nullable()->comment('錢包说明');
            $table->string('en_wallet_explain', 255)->nullable()->comment('錢包说明(英文)');
            $table->decimal('wallet_money', 10, 2)->comment('錢包金额');
            $table->integer('wallet_status')->default(1)->comment('1：正常  2：禁用  -1：删除');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_wallet_record');
    }
}
