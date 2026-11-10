<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 後台新增「查賬」子菜單（掛在「訂單管理」下）。
 * 幂等插入，重複執行不會產生重複項。
 */
return new class extends Migration {

    public function up(): void
    {
        $now = date('Y-m-d H:i:s');
        $parent = 19;   // 19 = 訂單管理
        $path = '/account-audit';

        if (DB::table('menu')->where('path', $path)->exists()) {
            return;
        }

        $menuId = DB::table('menu')->insertGetId([
            'menu_name'    => '查賬',
            'en_menu_name' => 'アカウント照合',
            'parent'       => $parent,
            'icon'         => null,
            'path'         => $path,
            'redirect'     => 'audit/account-audit',
            'status'       => 1,
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);

        // 授權角色：1 = 超級管理員，2 = 老板（其餘角色可在「權限管理-角色」中自行勾選）
        foreach ([1, 2] as $roleId) {
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
    }

    public function down(): void
    {
        $menuIds = DB::table('menu')->where('path', '/account-audit')->pluck('menu_id')->all();
        if (!empty($menuIds)) {
            DB::table('role_menu')->whereIn('menu_id', $menuIds)->delete();
            DB::table('menu')->whereIn('menu_id', $menuIds)->delete();
        }
    }
};
