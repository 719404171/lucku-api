<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOauthAccessTokensTable extends Migration
{
    public function up()
    {
        Schema::create('oauth_access_tokens', function (Blueprint $table) {
            $table->string('id', 100);
            $table->bigInteger('user_id')->nullable();
            $table->integer('client_id')->unsigned();
            $table->string('name', 191)->nullable();
            $table->text('scopes')->nullable();
            $table->tinyInteger('revoked');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->dateTime('expires_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('oauth_access_tokens');
    }
}
