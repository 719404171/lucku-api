<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShopSettingTable extends Migration
{
    public function up()
    {
        Schema::create('shop_setting', function (Blueprint $table) {
            $table->increments('shop_id')->comment('店鋪設置id');
            $table->string('shop_name', 255)->nullable()->comment('店鋪名稱');
            $table->string('shop_address', 255)->nullable()->comment('店鋪地址');
            $table->string('en_shop_address', 255)->nullable()->comment('英文店鋪地址');
            $table->string('shop_phone', 255)->nullable()->comment('店鋪電話');
            $table->string('shop_mail', 255)->nullable()->comment('店鋪郵箱');
            $table->string('shop_web', 255)->nullable()->comment('後台客服地址');
            $table->string('shop_logo', 255)->nullable()->comment('店鋪logo');
            $table->integer('is_tips')->default(0)->comment('是否開啓低庫存提醒  0否 1是');
            $table->integer('minStock')->nullable()->default(1)->comment('設置低庫存提醒');
            $table->integer('recharge_min')->nullable()->comment('充值最小金額');
            $table->integer('recharge_max')->nullable()->comment('充值最大金額');
            $table->integer('is_freight')->default(0)->comment('是否设置运费  0否 1是');
            $table->decimal('freight_price', 10, 2)->default(0.00)->comment('运费');
            $table->integer('is_tostore')->default(0)->comment('是否開啓預約到店查看價格 0否 1是');
            $table->decimal('tostore_price', 10, 2)->default(0.00)->comment('預約到店查看價格');
            $table->integer('booking_limit')->nullable()->default(0)->comment('是否启用预约人数上限 0否 1是');
            $table->integer('booking_limit_num')->nullable()->comment('预约人数上限');
            $table->integer('userlogin_mechanism')->nullable()->default(1)->comment('是否啓用用戶保底機制(1啓用2)');
            $table->integer('shop_status')->default(1)->comment('狀態1：正常 0：禁用-1：删除');
            $table->date('release_date')->default('2025-08-05');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('shop_setting');
    }
}
