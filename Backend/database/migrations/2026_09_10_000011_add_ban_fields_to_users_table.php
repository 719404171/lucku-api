<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 用戶表增加「臨時封禁」欄位：對賬時發現異常賬單可先封禁賬號。
 * ban_until = NULL 且有 ban_reason 時表示永久封禁；封禁同時把 user_status 置 0（App 端無法登錄/請求）。
 */
return new class extends Migration {

    public function up(): void
    {
        if (!Schema::hasColumn('users', 'ban_until')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dateTime('ban_until')->nullable()->comment('臨時封禁到期時間，NULL=未封禁或永久封禁');
                $table->string('ban_reason', 255)->nullable()->comment('封禁原因');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'ban_until')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn(['ban_until', 'ban_reason']);
            });
        }
    }
};
