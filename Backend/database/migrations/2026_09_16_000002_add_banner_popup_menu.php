<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Banner弹窗：在 Banner 管理 (menu_id=41) 下新增子菜单
 * parent=41, path=/banner-popup, redirect=banner/banner-popup
 * 授权角色：1=超级管理员, 2=老板, 3=运营 (与 Banner 一致)
 * 全部幂等，可重复执行。
 */
return new class extends Migration {

    public function up(): void
    {
        // 确认 banner_popup 表存在
        if (!Schema::hasTable('banner_popup')) {
            echo "\n[Banner弹窗] banner_popup 表不存在，請先執行建表 migration\n";
            return;
        }

        // 父級 Banner 管理 menu_id=41
        $parentId = 41;
        if (!DB::table('menu')->where('menu_id', $parentId)->exists()) {
            echo "\n[Banner弹窗] 父級菜單 Banner管理 (id=41) 不存在\n";
            return;
        }

        $path = '/banner-popup';
        if (DB::table('menu')->where('path', $path)->exists()) {
            echo "\n[Banner弹窗] 菜單 {$path} 已存在，跳過\n";
            return;
        }

        $now = date('Y-m-d H:i:s');
        $menuId = DB::table('menu')->insertGetId([
            'menu_name'    => 'Banner弹窗',
            'en_menu_name' => 'Banner Popup',
            'parent'       => $parentId,
            'icon'         => null,
            'path'         => $path,
            'redirect'     => 'banner/banner-popup',
            'status'       => 1,
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);

        // 授權角色：1 / 2 / 3
        $roleIds = [1, 2, 3];
        foreach ($roleIds as $roleId) {
            if (!DB::table('role')->where('role_id', $roleId)->exists()) {
                continue;
            }
            DB::table('role_menu')->insert([
                'role_id'    => $roleId,
                'menu_id'    => $menuId,
                'status'     => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        echo "\n[Banner弹窗] 菜單新增成功，menu_id={$menuId}\n";
    }

    public function down(): void
    {
        $menuIds = DB::table('menu')->where('path', '/banner-popup')->pluck('menu_id')->all();
        if (!empty($menuIds)) {
            DB::table('role_menu')->whereIn('menu_id', $menuIds)->delete();
            DB::table('menu')->whereIn('menu_id', $menuIds)->delete();
        }
    }
};
