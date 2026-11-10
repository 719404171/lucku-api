<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 卡牌多用戶抽取檢測頁面：
 * 1) order_details 補查詢索引（按卡牌 / 用戶 / 時間分組檢索）；
 * 2) 後台「用戶管理」下新增子菜單「卡牌多用戶抽取檢測」（menu + role_menu，role 1/2）。
 * 全部幂等，可重複執行。
 */
return new class extends Migration {

    private function safeIndex(string $table, \Closure $definition): void
    {
        try {
            Schema::table($table, $definition);
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            // 已存在同名索引時忽略（MySQL 1061 Duplicate key name）
            if (strpos($msg, 'Duplicate key name') !== false) {
                return;
            }
            // 表不存在時跳過（SQLSTATE[42S02]），避免整批索引中斷
            if (strpos($msg, '42S02') !== false || stripos($msg, "doesn't exist") !== false) {
                echo "\n[安全索引] 表 {$table} 不存在，已跳過\n";
                return;
            }
            throw $e;
        }
    }

    public function up(): void
    {
        // 多用戶抽取檢測：以 product_id 分組，再按 user_id / created_at 過濾
        $this->safeIndex('order_details', function (Blueprint $table) {
            $table->index('product_id', 'idx_od_product');
        });
        $this->safeIndex('order_details', function (Blueprint $table) {
            $table->index('user_id', 'idx_od_user');
        });
        $this->safeIndex('order_details', function (Blueprint $table) {
            $table->index('created_at', 'idx_od_created');
        });
        // 抽卡訂單過濾（indent_type=2 + order_status）
        $this->safeIndex('order', function (Blueprint $table) {
            $table->index(['indent_type', 'order_status'], 'idx_order_type_status');
        });

        // 菜單：父級 9 = 用戶管理，path 決定前端組件（父目錄名 multi-draw-check → MultiDrawCheck）
        $path = '/multi-draw-check';
        if (!DB::table('menu')->where('path', $path)->exists()) {
            $now = date('Y-m-d H:i:s');
            $menuId = DB::table('menu')->insertGetId([
                'menu_name'    => '卡牌多用戶抽取檢測',
                'en_menu_name' => 'Multi-User Draw Check',
                'parent'       => 9,
                'icon'         => null,
                'path'         => $path,
                'redirect'     => 'order/multi-draw-check',
                'status'       => 1,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);

            // 授權角色：1 = 超級管理員，2 = 老板
            $roleIds = [1, 2];
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
        $names = [
            'order_details' => ['idx_od_product', 'idx_od_user', 'idx_od_created'],
            'order'         => ['idx_order_type_status'],
        ];
        foreach ($names as $table => $indexes) {
            foreach ($indexes as $name) {
                try {
                    Schema::table($table, function (Blueprint $table) use ($name) {
                        $table->dropIndex($name);
                    });
                } catch (\Throwable $e) {
                    // 索引不存在時忽略
                }
            }
        }

        $menuIds = DB::table('menu')->where('path', '/multi-draw-check')->pluck('menu_id')->all();
        if (!empty($menuIds)) {
            DB::table('role_menu')->whereIn('menu_id', $menuIds)->delete();
            DB::table('menu')->whereIn('menu_id', $menuIds)->delete();
        }
    }
};
