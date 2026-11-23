<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 後台設置頁「登錄短信驗證」開關
 * 由後台設置頁控制，替代 .env 的 ADMIN_SMS_ENABLED
 */
class AddAdminSmsEnabledToShopSetting extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('shop_setting') || Schema::hasColumn('shop_setting', 'admin_sms_enabled')) {
            return;
        }
        Schema::table('shop_setting', function (Blueprint $table) {
            $table->tinyInteger('admin_sms_enabled')->default(0)->comment('後台登錄短信驗證：0關閉 1開啟');
        });
    }

    public function down()
    {
        if (!Schema::hasTable('shop_setting') || !Schema::hasColumn('shop_setting', 'admin_sms_enabled')) {
            return;
        }
        Schema::table('shop_setting', function (Blueprint $table) {
            $table->dropColumn('admin_sms_enabled');
        });
    }
}
