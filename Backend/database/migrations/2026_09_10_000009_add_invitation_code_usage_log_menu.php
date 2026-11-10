<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 後台在「用戶管理」下新增「邀請碼使用記錄」子菜單。
 * 幂等插入，重複執行不會產生重複項。
 */
return new class extends Migration {

    public function up(): void
    {
        $now = date('Y-m-d H:i:s');
        $parent = 9;   // 9 = 用戶管理
        $path = '/invitation-code-log';

        if (DB::table('menu')->where('path', $path)->exists()) {
            return;
        }

        $menuId = DB::table('menu')->insertGetId([
            'menu_name'    => '邀請碼使用記錄',
            'en_menu_name' => '招待コード使用履歴',
            'parent'       => $parent,
            'icon'         => null,
            'path'         => $path,
            'redirect'     => 'user/invitation-code-log',
            'status'       => 1,
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);

        // 與「邀請碼管理」菜單授權一致的角色，一併授權（找不到時退回 role 1/2）
        $roleIds = DB::table('role_menu')
            ->where('menu_id', 36)
            ->where('status', 1)
            ->pluck('role_id')
            ->all();
        if (empty($roleIds)) {
            $roleIds = [1, 2];
        }

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
    }

    public function down(): void
    {
        $menuIds = DB::table('menu')->where('path', '/invitation-code-log')->pluck('menu_id')->all();
        if (!empty($menuIds)) {
            DB::table('role_menu')->whereIn('menu_id', $menuIds)->delete();
            DB::table('menu')->whereIn('menu_id', $menuIds)->delete();
        }
    }
};
