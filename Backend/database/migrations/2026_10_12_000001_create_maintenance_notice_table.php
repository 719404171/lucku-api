<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 维护公告功能：建 maintenance_notice 表 + 在 menu 表插入"维护公告"子菜单
 */
return new class extends Migration {

    public function up(): void
    {
        if (!Schema::hasTable('maintenance_notice')) {
            Schema::create('maintenance_notice', function (Blueprint $table) {
                $table->tinyIncrements('id');
                $table->tinyInteger('maintenance_enabled')->default(0);
                $table->longText('notice_content')->nullable();
                $table->longText('en_notice_content')->nullable();
                $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            });

            DB::table('maintenance_notice')->insert([
                'id'                 => 1,
                'maintenance_enabled'=> 0,
                'notice_content'     => null,
                'en_notice_content'  => null,
            ]);
        }

        // 插入菜单记录（幂等：重複执行不會重複插入）
        $exists = DB::table('menu')->where('path', '/maintenance-notice')->exists();
        if (!$exists) {
            DB::table('menu')->insert([
                'parent'       => 29,
                'menu_name'    => '维护公告',
                'en_menu_name' => 'Maintenance Notice',
                'path'         => '/maintenance-notice',
                'redirect'     => 'shop/maintenance-notice',
                'icon'         => 'el-icon-notice',
                'status'       => 1,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('menu')->whereIn('path', ['maintenance-notice', '/maintenance-notice'])->delete();
        Schema::dropIfExists('maintenance_notice');
    }
};
