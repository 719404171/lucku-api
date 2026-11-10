<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 店鋪設置新增「APP端後台圖片」欄位：更換 APP 端後台背景圖片（建議尺寸 1375*768）
 */
return new class extends Migration {

    public function up(): void
    {
        if (!Schema::hasColumn('shop_setting', 'app_background_img')) {
            Schema::table('shop_setting', function (Blueprint $table) {
                $table->string('app_background_img', 255)->nullable()->comment('APP端後台圖片（建議尺寸1375*768）');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('shop_setting', 'app_background_img')) {
            Schema::table('shop_setting', function (Blueprint $table) {
                $table->dropColumn('app_background_img');
            });
        }
    }
};
