<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class AddMemberLevelLimitToCardSort extends Migration
{
    public function up()
    {
        if (Schema::hasTable('card_sort') && !Schema::hasColumn('card_sort', 'member_level_limit')) {
            Schema::table('card_sort', function ($table) {
                // 會員等級限制：存 user_member.member_id；0 = 不限制
                $table->integer('member_level_limit')->default(0)->comment('會員等級限制(member_id)，0=不限制');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('card_sort') && Schema::hasColumn('card_sort', 'member_level_limit')) {
            Schema::table('card_sort', function ($table) {
                $table->dropColumn('member_level_limit');
            });
        }
    }
}
