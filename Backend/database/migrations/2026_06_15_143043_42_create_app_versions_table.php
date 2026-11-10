<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAppVersionsTable extends Migration
{
    public function up()
    {
        Schema::create('app_versions', function (Blueprint $table) {
            $table->increments('versions_id');
            $table->string('versions_number', 11)->nullable();
            $table->string('versions_url', 255)->nullable();
            $table->string('versions_status', 11)->nullable()->default(1)->comment('1：正常  2：禁用  -1：删除');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('app_versions');
    }
}
