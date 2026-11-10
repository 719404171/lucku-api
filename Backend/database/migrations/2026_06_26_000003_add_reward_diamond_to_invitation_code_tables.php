<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRewardDiamondToInvitationCodeTables extends Migration
{
    public function up()
    {
        Schema::table('invitation_codes', function (Blueprint $table) {
            $table->integer('reward_diamond')->default(0)->comment('每次使用獎勵鑽石')->after('reward_integral');
        });
        Schema::table('invitation_code_usage_logs', function (Blueprint $table) {
            $table->integer('reward_diamond')->default(0)->comment('獲得鑽石')->after('reward_integral');
        });
    }

    public function down()
    {
        Schema::table('invitation_codes', function (Blueprint $table) {
            $table->dropColumn('reward_diamond');
        });
        Schema::table('invitation_code_usage_logs', function (Blueprint $table) {
            $table->dropColumn('reward_diamond');
        });
    }
}
