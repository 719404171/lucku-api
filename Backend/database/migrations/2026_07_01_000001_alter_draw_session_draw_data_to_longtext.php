<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterDrawSessionDrawDataToLongtext extends Migration
{
    public function up()
    {
        Schema::table('draw_session', function (Blueprint $table) {
            $table->longText('draw_data')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('draw_session', function (Blueprint $table) {
            $table->text('draw_data')->nullable()->change();
        });
    }
}
