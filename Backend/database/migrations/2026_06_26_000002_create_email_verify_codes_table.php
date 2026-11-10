<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmailVerifyCodesTable extends Migration
{
    public function up()
    {
        Schema::create('email_verify_codes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('email', 255);
            $table->string('code', 6);
            $table->tinyInteger('used')->default(0)->comment('0=未使用, 1=已使用');
            $table->timestamp('expires_at')->comment('到期時間');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('email_verify_codes');
    }
}
