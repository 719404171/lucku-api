<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLoginBonusToUserMemberTable extends Migration
{
    public function up()
    {
        Schema::table('user_member', function (Blueprint $table) {
            $table->integer('login_bonus_coin')->default(0)->comment('每日登錄送金幣');
            $table->integer('login_bonus_diamond')->default(0)->comment('每日登錄送鑽石');
        });
    }

    public function down()
    {
        Schema::table('user_member', function (Blueprint $table) {
            $table->dropColumn(['login_bonus_coin', 'login_bonus_diamond']);
        });
    }
}
