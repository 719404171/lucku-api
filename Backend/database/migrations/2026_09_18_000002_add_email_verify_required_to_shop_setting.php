<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('shop_setting', 'email_verify_required')) {
            Schema::table('shop_setting', function (Blueprint $table) {
                $table->unsignedTinyInteger('email_verify_required')
                    ->default(0)
                    ->comment('註冊是否需要郵箱驗證 0=不需要（關閉） 1=必須郵箱驗證（開啟）')
                    ->after('userlogin_mechanism');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('shop_setting', 'email_verify_required')) {
            Schema::table('shop_setting', function (Blueprint $table) {
                $table->dropColumn('email_verify_required');
            });
        }
    }
};
