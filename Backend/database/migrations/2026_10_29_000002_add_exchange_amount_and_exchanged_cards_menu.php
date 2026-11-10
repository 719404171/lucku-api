<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 後台「已兌換卡片恢復」功能：
 * 1. order_details 增加 exchange_amount（兌換時記錄實際兌換金額，恢復時按原額扣點）
 * 2. 註冊菜單「已兌換卡片」（卡片管理下）+ role_menu + power_menu(edit) 授權
 */
return new class extends Migration {

    public function up(): void
    {
        // 1. order_details 增加 exchange_amount
        if (!Schema::hasColumn('order_details', 'exchange_amount')) {
            Schema::table('order_details', function (Blueprint $table) {
                $table->decimal('exchange_amount', 10, 2)->nullable()->after('exchange_status')->comment('兌換時的實際金額（恢復時按此扣點）');
            });
        }

        // 2. 菜單註冊（冪等）
        $now = date('Y-m-d H:i:s');
        $path = '/exchanged-cards';
        $menu = DB::table('menu')->where('path', $path)->first();
        if (!$menu) {
            $menuId = DB::table('menu')->insertGetId([
                'menu_name'    => '已兌換卡片',
                'en_menu_name' => 'Exchanged Cards',
                'parent'       => 13, // 卡片管理
                'icon'         => null,
                'path'         => $path,
                'redirect'     => 'card/exchanged-cards',
                'status'       => 1,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
        } else {
            $menuId = $menu->menu_id;
        }

        // 3. role_menu：給已授權父級「卡片管理」(menu_id=13) 的角色授權，回退 [1,2]
        $roleIds = DB::table('role_menu')
            ->where('menu_id', 13)
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
            $exists = DB::table('role_menu')
                ->where('role_id', $roleId)
                ->where('menu_id', $menuId)
                ->exists();
            if (!$exists) {
                DB::table('role_menu')->insert([
                    'role_id'    => $roleId,
                    'menu_id'    => $menuId,
                    'status'     => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // 4. power_menu：edit 權限（控制「恢復」按鈕顯示）
        $editPower = DB::table('power')->where('type', 'edit')->first();
        if ($editPower) {
            foreach (array_unique($roleIds) as $roleId) {
                if (!DB::table('role')->where('role_id', $roleId)->exists()) {
                    continue;
                }
                $exists = DB::table('power_menu')
                    ->where('role_id', $roleId)
                    ->where('menu_id', $menuId)
                    ->where('power_id', $editPower->power_id)
                    ->exists();
                if (!$exists) {
                    DB::table('power_menu')->insert([
                        'power_id'   => $editPower->power_id,
                        'role_id'    => $roleId,
                        'menu_id'    => $menuId,
                        'status'     => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        $menuIds = DB::table('menu')->where('path', '/exchanged-cards')->pluck('menu_id')->all();
        if (!empty($menuIds)) {
            DB::table('power_menu')->whereIn('menu_id', $menuIds)->delete();
            DB::table('role_menu')->whereIn('menu_id', $menuIds)->delete();
            DB::table('menu')->whereIn('menu_id', $menuIds)->delete();
        }
        if (Schema::hasColumn('order_details', 'exchange_amount')) {
            Schema::table('order_details', function (Blueprint $table) {
                $table->dropColumn('exchange_amount');
            });
        }
    }
};
