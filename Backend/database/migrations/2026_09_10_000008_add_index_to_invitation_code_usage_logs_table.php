<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 邀請碼使用記錄的查詢索引：後台按邀請碼 + 使用者校驗「每人每碼只能用一次」，
 * 以及使用記錄列表的關聯查詢都會走這個複合索引。
 */
return new class extends Migration {

    public function up(): void
    {
        if (Schema::hasTable('invitation_code_usage_logs') && !$this->hasIndex()) {
            Schema::table('invitation_code_usage_logs', function (Blueprint $table) {
                $table->index(['invitation_id', 'user_id'], 'idx_icl_code_user');
            });
        }
    }

    public function down(): void
    {
        if ($this->hasIndex()) {
            Schema::table('invitation_code_usage_logs', function (Blueprint $table) {
                $table->dropIndex('idx_icl_code_user');
            });
        }
    }

    private function hasIndex(): bool
    {
        $indexes = DB::select('SHOW INDEX FROM invitation_code_usage_logs');
        foreach ($indexes as $index) {
            if (($index->Key_name ?? '') === 'idx_icl_code_user') {
                return true;
            }
        }
        return false;
    }
};
