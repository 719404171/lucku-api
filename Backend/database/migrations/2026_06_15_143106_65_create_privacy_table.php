<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePrivacyTable extends Migration
{
    public function up()
    {
        Schema::create('privacy', function (Blueprint $table) {
            $table->increments('privacy_id')->comment('隱私協議id');
            $table->text('privacy_content')->nullable()->comment('内容');
            $table->text('en_privacy_content')->nullable();
            $table->integer('privacy_status')->default(1)->comment('1 可用 2禁用  -1删除');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('privacy');
    }
}
