<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class AddMemberLevelLimitsToCardSort extends Migration
{
    public function up()
    {
        if (Schema::hasTable('card_sort') && !Schema::hasColumn('card_sort', 'member_level_limits')) {
            Schema::table('card_sort', function (Blueprint $table) {
                // 多會員等級限制 + 每等級可抽張數，JSON 陣列：
                // [{"member_id":3,"draw_limit":1},{"member_id":4,"draw_limit":2}]
                // draw_limit = 0 表示該等級不額外限制；欄位為空時回退舊字段 member_level_limit
                $table->text('member_level_limits')->nullable()->comment('多會員等級限制及每級可抽張數(JSON)');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('card_sort') && Schema::hasColumn('card_sort', 'member_level_limits')) {
            Schema::table('card_sort', function (Blueprint $table) {
                $table->dropColumn('member_level_limits');
            });
        }
    }
}
