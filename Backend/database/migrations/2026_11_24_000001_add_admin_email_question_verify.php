<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * 後台登錄增加「郵箱驗證」「安全問題驗證」
 * - shop_setting.admin_email_enabled / admin_question_enabled 開關
 * - admin_sms_code 增加 channel / target，複用為通用驗證碼表（sms / email）
 * - admin_security_question 管理員安全問題（可多個）
 */
class AddAdminEmailQuestionVerify extends Migration
{
    public function up()
    {
        // 1) 店鋪設置開關
        Schema::table('shop_setting', function (Blueprint $table) {
            if (!Schema::hasColumn('shop_setting', 'admin_email_enabled')) {
                $table->tinyInteger('admin_email_enabled')->default(0)->comment('後台登錄郵箱驗證：0關閉 1開啟');
            }
            if (!Schema::hasColumn('shop_setting', 'admin_question_enabled')) {
                $table->tinyInteger('admin_question_enabled')->default(0)->comment('後台登錄安全問題驗證：0關閉 1開啟');
            }
        });

        // 2) 驗證碼表擴展為通用（短信 / 郵箱）
        Schema::table('admin_sms_code', function (Blueprint $table) {
            if (!Schema::hasColumn('admin_sms_code', 'channel')) {
                $table->string('channel', 10)->default('sms')->comment('驗證渠道：sms / email')->after('phone');
            }
            if (!Schema::hasColumn('admin_sms_code', 'target')) {
                $table->string('target', 128)->default('')->comment('接收目標：手機號 / 郵箱')->after('channel');
            }
        });

        // 3) 管理員安全問題表
        if (!Schema::hasTable('admin_security_question')) {
            Schema::create('admin_security_question', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->integer('admin_id')->default(0)->comment('管理員ID');
                $table->string('question', 255)->default('')->comment('問題');
                $table->string('answer_hash', 255)->default('')->comment('答案哈希（bcrypt）');
                $table->integer('sort')->default(0)->comment('排序');
                $table->tinyInteger('status')->default(1)->comment('1啟用 0停用');
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->index(['admin_id', 'status'], 'idx_asq_admin_status');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('admin_security_question');

        Schema::table('admin_sms_code', function (Blueprint $table) {
            if (Schema::hasColumn('admin_sms_code', 'channel')) {
                $table->dropColumn('channel');
            }
            if (Schema::hasColumn('admin_sms_code', 'target')) {
                $table->dropColumn('target');
            }
        });

        Schema::table('shop_setting', function (Blueprint $table) {
            if (Schema::hasColumn('shop_setting', 'admin_email_enabled')) {
                $table->dropColumn('admin_email_enabled');
            }
            if (Schema::hasColumn('shop_setting', 'admin_question_enabled')) {
                $table->dropColumn('admin_question_enabled');
            }
        });
    }
}
