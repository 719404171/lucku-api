<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvitationCodesTable extends Migration
{
    public function up()
    {
        Schema::create('invitation_codes', function (Blueprint $table) {
            $table->increments('invitation_id');
            $table->string('code', 50)->unique()->comment('邀請碼');
            $table->integer('use_count')->default(0)->comment('已使用次數');
            $table->integer('max_uses')->default(1)->comment('最大使用次數 0=不限');
            $table->integer('reward_coin')->default(0)->comment('每次使用獎勵金幣');
            $table->integer('reward_integral')->default(0)->comment('每次使用獎勵積分');
            $table->tinyInteger('status')->default(1)->comment('狀態 1:啟用 0:禁用');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('invitation_codes');
    }
}
