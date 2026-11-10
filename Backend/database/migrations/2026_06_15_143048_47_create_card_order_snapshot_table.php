<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCardOrderSnapshotTable extends Migration
{
    public function up()
    {
        Schema::create('card_order_snapshot', function (Blueprint $table) {
            $table->increments('card_order_snapshot_id')->comment('訂單快照ID');
            $table->integer('card_order_details_id')->comment('訂單詳情ID');
            $table->text('coupon_snapshot')->nullable()->comment('優惠券快照');
            $table->text('user_snapshot')->nullable()->comment('用戶快照');
            $table->text('card_snapshot')->nullable()->comment('產品快照');
            $table->integer('snapshot_status')->default(1)->comment('1：正常0：禁用-1：删除');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('card_order_snapshot');
    }
}
