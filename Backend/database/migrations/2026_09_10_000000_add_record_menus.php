<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 後台新增「用戶充值記錄 / 抽卡記錄 / 抽卡明細」三個子菜單。
 * 菜單由數據庫下發（menu + role_menu），此處幂等插入，重複執行不會產生重複項。
 */
return new class extends Migration {

    public function up(): void
    {
        $now = date('Y-m-d H:i:s');

        // [菜單名, 日文名, 父級 menu_id, path, 組件路徑]
        // 父級 9 = 用戶管理，19 = 訂單管理
        $menus = [
            ['用戶充值記錄', 'チャージ履歴', 9, '/recharge-record', 'user/recharge-record'],
            ['抽卡記錄', 'カード抽選履歴', 19, '/draw-record', 'order/draw-record'],
            ['抽卡明細', 'カード抽選明細', 19, '/draw-detail', 'order/draw-detail'],
        ];

        // 授權角色：1 = 超級管理員，2 = 老板（其餘角色可在「權限管理-角色」中自行勾選）
        $roleIds = [1, 2];

        foreach ($menus as $menu) {
            if (DB::table('menu')->where('path', $menu[3])->exists()) {
                continue;
            }
            $menuId = DB::table('menu')->insertGetId([
                'menu_name'    => $menu[0],
                'en_menu_name' => $menu[1],
                'parent'       => $menu[2],
                'icon'         => null,
                'path'         => $menu[3],
                'redirect'     => $menu[4],
                'status'       => 1,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);

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
    }

    public function down(): void
    {
        $menuIds = DB::table('menu')
            ->whereIn('path', ['/recharge-record', '/draw-record', '/draw-detail'])
            ->pluck('menu_id')
            ->all();

        if (!empty($menuIds)) {
            DB::table('role_menu')->whereIn('menu_id', $menuIds)->delete();
            DB::table('menu')->whereIn('menu_id', $menuIds)->delete();
        }
    }
};
