<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 後台登錄短信驗證碼記錄
 * 多伺服器共用同一個 DB，因此用表存儲而不是本地快取
 */
class CreateAdminSmsCodeTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('admin_sms_code')) {
            return;
        }
        Schema::create('admin_sms_code', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('admin_id')->default(0)->comment('管理員ID');
            $table->string('phone', 20)->default('')->comment('接收手機號（不含國家碼）');
            $table->string('code_hash', 64)->default('')->comment('驗證碼 sha256');
            $table->datetime('expire_at')->nullable()->comment('過期時間');
            $table->tinyInteger('attempts')->default(0)->comment('校驗失敗次數');
            $table->datetime('used_at')->nullable()->comment('使用時間');
            $table->string('send_ip', 45)->default('')->comment('請求IP');
            $table->timestamps();

            $table->index(['admin_id', 'used_at'], 'idx_asc_admin_used');
        });
    }

    public function down()
    {
        Schema::dropIfExists('admin_sms_code');
    }
}
