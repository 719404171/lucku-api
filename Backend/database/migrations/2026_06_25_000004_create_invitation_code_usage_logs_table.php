<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvitationCodeUsageLogsTable extends Migration
{
    public function up()
    {
        Schema::create('invitation_code_usage_logs', function (Blueprint $table) {
            $table->increments('log_id');
            $table->integer('invitation_id')->comment('邀請碼ID');
            $table->string('code', 50)->comment('邀請碼');
            $table->integer('user_id')->comment('使用人user_id');
            $table->integer('reward_coin')->default(0)->comment('獲得金幣');
            $table->integer('reward_integral')->default(0)->comment('獲得積分');
            $table->dateTime('used_at')->comment('使用時間');
            $table->timestamps();

            $table->index('invitation_id');
            $table->index('user_id');
            $table->index('code');
        });
    }

    public function down()
    {
        Schema::dropIfExists('invitation_code_usage_logs');
    }
}
