<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 「抽卡明細」菜單更名為「账单明细」。
 * 幂等：重複執行不會出錯。
 */
return new class extends Migration {

    public function up(): void
    {
        DB::table('menu')
            ->where('path', '/draw-detail')
            ->update([
                'menu_name'  => '账单明细',
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }

    public function down(): void
    {
        DB::table('menu')
            ->where('path', '/draw-detail')
            ->update([
                'menu_name'  => '抽卡明細',
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }
};
