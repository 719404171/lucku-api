<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * users.user_email 唯一索引：
 *  - 杜絕「連點註冊/並發請求」寫入多個相同郵箱帳號
 *  - 同時為郵箱登錄提供索引，避免每次按郵箱查詢都全表掃描
 * 空字符串郵箱會互相衝突，先統一改為 NULL（MySQL 唯一索引允許多個 NULL）。
 */
return new class extends Migration {

    private function hasUniqueIndex(): bool
    {
        $indexes = DB::select('SHOW INDEX FROM `users`');
        foreach ($indexes as $index) {
            if ($index->Key_name === 'uniq_users_email') {
                return true;
            }
        }
        return false;
    }

    public function up(): void
    {
        if ($this->hasUniqueIndex()) {
            return;
        }

        // 空字符串郵箱統一為 NULL，否則多條空串會互相衝突
        DB::table('users')->where('user_email', '')->update(['user_email' => null]);

        // 仍有重複郵箱時直接報錯，避免索引建不上卻被誤認為已生效
        $duplicate = DB::table('users')
            ->selectRaw('LOWER(TRIM(user_email)) as email_lower, COUNT(*) as total')
            ->whereNotNull('user_email')
            ->where('user_email', '<>', '')
            ->groupBy('email_lower')
            ->havingRaw('total > 1')
            ->first();
        if (!empty($duplicate)) {
            throw new \RuntimeException(
                'users 表仍存在重複郵箱（例如 ' . $duplicate->email_lower . '），請先執行：php artisan users:dedupe-email --force'
            );
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unique('user_email', 'uniq_users_email');
        });
    }

    public function down(): void
    {
        if (!$this->hasUniqueIndex()) {
            return;
        }
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('uniq_users_email');
        });
    }
};
