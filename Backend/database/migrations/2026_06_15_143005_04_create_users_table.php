<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('user_id')->comment('用户id');
            $table->string('user_number', 22)->nullable()->comment('用户會員卡號(*)');
            $table->string('user_name', 255)->nullable()->comment('用戶姓名');
            $table->string('user_email', 100)->nullable()->comment('用戶邮箱');
            $table->string('user_password', 191)->nullable()->comment('用戶密码');
            $table->string('area_code', 20)->nullable()->default(86)->comment('用户電話區號');
            $table->string('user_phone', 15)->nullable()->comment('用戶手机号');
            $table->integer('user_integral')->default(0)->comment('现有可用積分');
            $table->integer('user_integral_all')->default(0)->comment('用户總積分');
            $table->text('user_avatar')->nullable()->comment('用戶头像');
            $table->integer('user_sex')->default(1)->comment('用户性别  1男  0女');
            $table->string('user_profession', 100)->nullable()->comment('用戶职业');
            $table->string('user_autograph', 255)->nullable()->comment('用戶个性签名');
            $table->dateTime('user_birthday')->nullable()->comment('用户生日');
            $table->decimal('user_balance', 10, 2)->nullable()->default(0.00)->comment('用戶余额');
            $table->string('open_id', 255)->nullable()->comment('用户第三方登錄的open_id');
            $table->string('client_id', 255)->nullable()->comment('用户推送的id');
            $table->integer('login_type')->default(1)->comment('登錄類型  1邮箱登录    2蘋果登錄  3谷歌登錄  4apple登錄');
            $table->string('token_type', 255)->nullable()->comment('用户手机系统');
            $table->string('user_code', 255)->nullable()->comment('用戶二維碼');
            $table->string('user_card', 255)->nullable()->comment('用戶名片');
            $table->integer('user_status')->nullable()->default(1)->comment('1 可用 0禁用  -1删除');
            $table->integer('login_time')->default(0)->comment('用户登錄次數');
            $table->dateTime('created_at')->nullable()->comment('用户创建时间');
            $table->dateTime('updated_at')->nullable()->comment('用户更新时间');
            $table->dateTime('finally_at')->nullable()->comment('用户最後登錄時間');
            $table->string('stripe_id', 110)->nullable()->comment('stripe支付订阅ID');
            $table->decimal('user_coin', 10, 2)->default(0.00)->comment('用户金币');
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
}
