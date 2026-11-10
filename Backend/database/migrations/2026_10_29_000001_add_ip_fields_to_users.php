<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIpFieldsToUsers extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'register_ip')) {
                $table->string('register_ip', 45)->nullable()->comment('註冊IP')->after('user_status');
            }
            if (!Schema::hasColumn('users', 'last_login_ip')) {
                $table->string('last_login_ip', 45)->nullable()->comment('最後登錄IP')->after('register_ip');
            }
        });

        if (!Schema::hasTable('user_request_log')) {
            Schema::create('user_request_log', function (Blueprint $table) {
                $table->bigIncrements('log_id');
                $table->unsignedInteger('user_id')->index();
                $table->string('ip', 45)->index();
                $table->string('method', 10);
                $table->string('path', 255);
                $table->timestamp('created_at')->useCurrent()->index();
            });
        }
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['register_ip', 'last_login_ip']);
        });
        Schema::dropIfExists('user_request_log');
    }
}
