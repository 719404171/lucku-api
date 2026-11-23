<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 後台登錄安全加固：
 * 1. admins.token_expire_at   登錄令牌服務端過期
 * 2. admin_log 失敗登錄記錄   login_status / login_msg
 * 3. admin_operation_log     後台操作審計
 */
class HardenAdminAuth extends Migration
{
    public function up()
    {
        if (Schema::hasTable('admins') && !Schema::hasColumn('admins', 'token_expire_at')) {
            Schema::table('admins', function (Blueprint $table) {
                $table->datetime('token_expire_at')->nullable()->comment('登錄令牌過期時間');
            });
        }

        if (Schema::hasTable('admin_log')) {
            if (!Schema::hasColumn('admin_log', 'login_status')) {
                Schema::table('admin_log', function (Blueprint $table) {
                    $table->tinyInteger('login_status')->default(1)->comment('1成功 0失敗');
                });
            }
            if (!Schema::hasColumn('admin_log', 'login_msg')) {
                Schema::table('admin_log', function (Blueprint $table) {
                    $table->string('login_msg', 255)->default('')->comment('失敗原因');
                });
            }
        }

        if (!Schema::hasTable('admin_operation_log')) {
            Schema::create('admin_operation_log', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->integer('admin_id')->default(0)->comment('操作人管理員ID');
                $table->string('username', 64)->default('')->comment('操作人帳號');
                $table->string('method', 10)->default('')->comment('請求方法');
                $table->string('path', 255)->default('')->comment('接口路徑');
                $table->text('params')->nullable()->comment('請求參數摘要');
                $table->string('ip', 45)->default('')->comment('請求IP');
                $table->string('user_agent', 255)->default('')->comment('User-Agent');
                $table->datetime('created_at')->nullable()->comment('操作時間');

                $table->index(['admin_id', 'created_at'], 'idx_aol_admin_time');
                $table->index('created_at', 'idx_aol_time');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('admin_operation_log');
    }
}
