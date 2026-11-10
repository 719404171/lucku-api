<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 补丁：为「维护公告」菜单补上 power_menu 权限（edit）
 *
 * 前端保存按钮通过 v-if="nodePower.includes('edit')" 控制显示，
 * nodePower 来自 $route.meta.power，而后端 getAdminMenuWeb
 * 仅对子菜单查询 power_menu 表，按当前用户 role_id 过滤。
 *
 * 之前只做了 role_menu 菜单授权，没有给该菜单创建 power_menu 记录，
 * 导致 power 为空数组 → nodePower.includes('edit') === false → 按钮不显示。
 *
 * 本迁移幂等：确保 menu 记录存在，为已通过 role_menu 授权的角色
 * 补上 power_id=3（type='edit'）的 power_menu 记录。
 */
return new class extends Migration {

    public function up(): void
    {
        $now = date('Y-m-d H:i:s');
        $path = '/maintenance-notice';

        // 1. 找到维护公告菜单
        $menu = DB::table('menu')->where('path', $path)->first();
        if (!$menu) {
            $this->warn("维护公告菜单 (path={$path}) 不存在，跳过 power_menu 授权");
            return;
        }
        $menuId = $menu->menu_id;

        // 2. 从 role_menu 获取已授权的角色
        $roleIds = DB::table('role_menu')
            ->where('menu_id', $menuId)
            ->where('status', 1)
            ->distinct()
            ->pluck('role_id')
            ->all();

        // 如果 role_menu 还没授权，回退到父级「店铺管理」已授权角色，再退回 1/2
        if (empty($roleIds)) {
            $roleIds = DB::table('role_menu')
                ->where('menu_id', 29)
                ->where('status', 1)
                ->distinct()
                ->pluck('role_id')
                ->all();
        }
        if (empty($roleIds)) {
            $roleIds = [1, 2];
        }

        // 3. 确认 edit 权限存在（power_id=3）
        $editPower = DB::table('power')->where('type', 'edit')->first();
        if (!$editPower) {
            $this->warn("power 表找不到 type='edit' 的权限，跳过");
            return;
        }
        $powerId = $editPower->power_id;

        // 4. 幂等插入：每个 role_id + menu_id + power_id 仅一条
        foreach (array_unique($roleIds) as $roleId) {
            if (!DB::table('role')->where('role_id', $roleId)->exists()) {
                continue;
            }
            $exists = DB::table('power_menu')
                ->where('role_id', $roleId)
                ->where('menu_id', $menuId)
                ->where('power_id', $powerId)
                ->exists();
            if ($exists) {
                continue;
            }
            DB::table('power_menu')->insert([
                'power_id'   => $powerId,
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
            DB::table('power_menu')->whereIn('menu_id', $menuIds)->delete();
        }
    }
};
