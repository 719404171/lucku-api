<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 把「抽卡記錄 / 抽卡明細 / 查賬」三個菜單從「訂單管理」移到「用戶管理」下。
 * 幂等：重複執行不會出錯。
 */
return new class extends Migration {

    public function up(): void
    {
        DB::table('menu')
            ->whereIn('path', ['/draw-record', '/draw-detail', '/account-audit'])
            ->update([
                'parent'     => 9,   // 9 = 用戶管理
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }

    public function down(): void
    {
        DB::table('menu')
            ->whereIn('path', ['/draw-record', '/draw-detail', '/account-audit'])
            ->update([
                'parent'     => 19,  // 19 = 訂單管理
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }
};
