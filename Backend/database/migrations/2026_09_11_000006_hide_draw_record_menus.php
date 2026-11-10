<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 後台隱藏「用戶管理」下的「抽卡記錄」與「账单明细」兩個子菜單。
 * 僅將 menu.status 由 1（顯示）改為 2（禁用不顯示），不刪除菜單與資料，
 * 需要恢復時執行 down 或將 status 改回 1 即可。
 */
return new class extends Migration {

    private $paths = ['/draw-record', '/draw-detail'];

    public function up(): void
    {
        DB::table('menu')
            ->whereIn('path', $this->paths)
            ->update([
                'status'     => 2,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }

    public function down(): void
    {
        DB::table('menu')
            ->whereIn('path', $this->paths)
            ->update([
                'status'     => 1,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }
};
