<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_send_log', function (Blueprint $table) {
            $table->increments('log_id');
            $table->string('to_email', 128)->comment('收件人');
            $table->string('subject', 255)->default('')->comment('郵件標題');
            $table->string('mailer', 64)->default('')->comment('使用的 mailer（smtp / smtp2）');
            $table->unsignedTinyInteger('attempt')->default(1)->comment('第幾次發送（1 或 2）');
            $table->unsignedTinyInteger('status')->default(0)->comment('0=發送中 1=成功 2=失敗');
            $table->text('error_msg')->nullable()->comment('失敗原因');
            $table->unsignedInteger('duration_ms')->default(0)->comment('耗時毫秒');
            $table->timestamps();

            $table->index('to_email');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_send_log');
    }
};
