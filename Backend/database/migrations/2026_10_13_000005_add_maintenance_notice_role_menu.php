<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 补丁：为「维护公告」菜单补上 role_menu 授权
 *
 * 后台菜单由数据库下发，前端 getAdminMenuWeb 通过
 *   menu -> role_menu -> role -> admin_role -> admins
 * 的 JOIN 按当前登录用户角色过滤菜单。之前只往 menu 表插了记录，
 * 却没有 role_menu 授权，导致任何用户都看不到「维护公告」菜单。
 *
 * 本迁移幂等：确保 menu 记录存在，并为父级「店铺管理」(menu_id=29)
 * 已授权的角色补上 role_menu 关联；找不到时退回 role 1/2。
 */
return new class extends Migration {

    public function up(): void
    {
        $now = date('Y-m-d H:i:s');
        $path = '/maintenance-notice';
        $parentId = 29;

        // 1. 确保 menu 记录存在（防御：正常情况下 000004 已创建）
        $menu = DB::table('menu')->where('path', $path)->first();
        if (!$menu) {
            $menuId = DB::table('menu')->insertGetId([
                'menu_name'    => '维护公告',
                'en_menu_name' => 'Maintenance Notice',
                'parent'       => $parentId,
                'icon'         => 'el-icon-notice',
                'path'         => $path,
                'redirect'     => 'shop/maintenance-notice',
                'status'       => 1,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
        } else {
            $menuId = $menu->menu_id;
        }

        // 2. 角色继承：父级「店铺管理」已授权角色，一并授权（找不到退回 1/2）
        $roleIds = DB::table('role_menu')
            ->where('menu_id', $parentId)
            ->where('status', 1)
            ->distinct()
            ->pluck('role_id')
            ->all();
        if (empty($roleIds)) {
            $roleIds = [1, 2];
        }

        foreach (array_unique($roleIds) as $roleId) {
            if (!DB::table('role')->where('role_id', $roleId)->exists()) {
                continue;
            }
            // 幂等：已存在则跳过
            $exists = DB::table('role_menu')
                ->where('role_id', $roleId)
                ->where('menu_id', $menuId)
                ->exists();
            if ($exists) {
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
    }

    public function down(): void
    {
        $menuIds = DB::table('menu')
            ->whereIn('path', ['/maintenance-notice', 'maintenance-notice'])
            ->pluck('menu_id')
            ->all();
        if (!empty($menuIds)) {
            DB::table('role_menu')->whereIn('menu_id', $menuIds)->delete();
        }
    }
};
