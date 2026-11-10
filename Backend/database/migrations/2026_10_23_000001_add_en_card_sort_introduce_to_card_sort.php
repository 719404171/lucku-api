<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 生產庫缺少卡池英文介紹欄位，導致英文模式下查詢報錯：
 * SQLSTATE[42S22]: Column not found: 1054 Unknown column 'card_sort.en_card_sort_introduce'
 * 此處補上該欄位（已存在時自動跳過）。
 * 代碼側 CardSort 已有 Schema 兜底，遷移未執行時會回退中文介紹，不會再報錯。
 */
return new class extends Migration {

    public function up(): void
    {
        if (Schema::hasTable('card_sort') && !Schema::hasColumn('card_sort', 'en_card_sort_introduce')) {
            Schema::table('card_sort', function (Blueprint $table) {
                $table->text('en_card_sort_introduce')->nullable()->comment('卡牌分類介绍（英）')->after('card_sort_introduce');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('card_sort') && Schema::hasColumn('card_sort', 'en_card_sort_introduce')) {
            Schema::table('card_sort', function (Blueprint $table) {
                $table->dropColumn('en_card_sort_introduce');
            });
        }
    }
};
