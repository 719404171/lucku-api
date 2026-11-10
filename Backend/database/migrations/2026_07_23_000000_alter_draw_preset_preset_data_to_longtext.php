<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterDrawPresetPresetDataToLongtext extends Migration
{
    public function up()
    {
        Schema::table('draw_preset', function (Blueprint $table) {
            $table->longText('preset_data')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('draw_preset', function (Blueprint $table) {
            $table->text('preset_data')->nullable()->change();
        });
    }
}
