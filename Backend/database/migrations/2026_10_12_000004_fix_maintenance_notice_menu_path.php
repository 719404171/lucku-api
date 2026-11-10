<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 补丁：修复"维护公告"菜单 path 缺少前导斜杠的问题
 * 前端动态路由依赖 path.substr(1) 映射组件，path 必须为 /maintenance-notice
 */
return new class extends Migration {

    public function up(): void
    {
        // 1. 删除之前可能插入的错误记录（path 缺少前导斜杠）
        DB::table('menu')->where('path', 'maintenance-notice')->delete();

        // 2. 确保正确的菜单记录存在（path 带前导斜杠 + redirect）
        $exists = DB::table('menu')->where('path', '/maintenance-notice')->exists();
        if (!$exists) {
            DB::table('menu')->insert([
                'menu_name'    => '维护公告',
                'en_menu_name' => 'Maintenance Notice',
                'parent'       => 29,
                'icon'         => 'el-icon-notice',
                'path'         => '/maintenance-notice',
                'redirect'     => 'shop/maintenance-notice',
                'status'       => 1,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        } else {
            // 已存在则补齐 redirect（若为空）
            DB::table('menu')->where('path', '/maintenance-notice')
                ->whereNull('redirect')
                ->update(['redirect' => 'shop/maintenance-notice']);
        }
    }

    public function down(): void
    {
        DB::table('menu')->whereIn('path', ['maintenance-notice', '/maintenance-notice'])->delete();
    }
};
