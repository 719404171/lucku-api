<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 邀請碼使用記錄 (invitation_id, user_id) 由普通索引改為唯一索引（第5層兜底）：
 * 即使 Redis 鎖失效或服務重啟，重複提交也無法重複寫入記錄、重複發獎。
 */
return new class extends Migration {

    private function hasIndex(string $name): bool
    {
        $indexes = DB::select('SHOW INDEX FROM `invitation_code_usage_logs`');
        foreach ($indexes as $index) {
            if ($index->Key_name === $name) {
                return true;
            }
        }
        return false;
    }

    public function up(): void
    {
        // 已有重複時先報錯，避免索引建不上卻被誤認為已生效
        $duplicate = DB::table('invitation_code_usage_logs')
            ->selectRaw('invitation_id, user_id, COUNT(*) as total')
            ->whereNotNull('invitation_id')
            ->whereNotNull('user_id')
            ->groupBy('invitation_id', 'user_id')
            ->havingRaw('total > 1')
            ->first();
        if (!empty($duplicate)) {
            throw new \RuntimeException(
                'invitation_code_usage_logs 存在重複的 (invitation_id, user_id)：invitation_id='
                .$duplicate->invitation_id.' user_id='.$duplicate->user_id.'，請先清理重複記錄再執行'
            );
        }

        if ($this->hasIndex('idx_icl_code_user')) {
            Schema::table('invitation_code_usage_logs', function (Blueprint $table) {
                $table->dropIndex('idx_icl_code_user');
            });
        }
        if (!$this->hasIndex('uniq_icl_code_user')) {
            Schema::table('invitation_code_usage_logs', function (Blueprint $table) {
                $table->unique(['invitation_id', 'user_id'], 'uniq_icl_code_user');
            });
        }
    }

    public function down(): void
    {
        if ($this->hasIndex('uniq_icl_code_user')) {
            Schema::table('invitation_code_usage_logs', function (Blueprint $table) {
                $table->dropUnique('uniq_icl_code_user');
            });
        }
        if (!$this->hasIndex('idx_icl_code_user')) {
            Schema::table('invitation_code_usage_logs', function (Blueprint $table) {
                $table->index(['invitation_id', 'user_id'], 'idx_icl_code_user');
            });
        }
    }
};
