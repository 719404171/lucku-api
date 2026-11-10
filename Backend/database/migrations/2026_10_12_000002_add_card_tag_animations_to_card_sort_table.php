<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 卡池卡片標籤對應中獎動畫：card_sort 新增 card_tag_animations（JSON 對象，標籤=>動畫URL）
 * 未設置動畫的標籤在抽獎時使用卡池默認動畫（card_sort_url_single/ten/hundred）
 */
return new class extends Migration {

    public function up(): void
    {
        if (!Schema::hasColumn('card_sort', 'card_tag_animations')) {
            Schema::table('card_sort', function (Blueprint $table) {
                $table->text('card_tag_animations')->nullable()->comment('標籤對應中獎動畫 JSON')->after('card_tag_order');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('card_sort', 'card_tag_animations')) {
            Schema::table('card_sort', function (Blueprint $table) {
                $table->dropColumn('card_tag_animations');
            });
        }
    }
};
