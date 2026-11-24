<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAdminLoginChallenge extends Migration
{
    /**
     * 後台登錄二次驗證 challenge 落庫（不依賴緩存配置）
     */
    public function up()
    {
        if (!Schema::hasTable('admin_login_challenge')) {
            Schema::create('admin_login_challenge', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->char('challenge_hash', 64)->default('');
                $table->integer('admin_id')->default(0);
                $table->string('username', 64)->default('');
                $table->string('methods', 32)->default('');
                $table->integer('question_id')->default(0);
                $table->tinyInteger('attempts')->default(0);
                $table->string('ip', 64)->default('');
                $table->dateTime('expire_at')->nullable();
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->index('challenge_hash', 'idx_alc_hash');
                $table->index('expire_at', 'idx_alc_expire');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('admin_login_challenge');
    }
}
