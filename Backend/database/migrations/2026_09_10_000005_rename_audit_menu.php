<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 「查賬」菜單更名為「账单查验」。
 * 幂等：重複執行不會出錯。
 */
return new class extends Migration {

    public function up(): void
    {
        DB::table('menu')
            ->where('path', '/account-audit')
            ->update([
                'menu_name'  => '账单查验',
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }

    public function down(): void
    {
        DB::table('menu')
            ->where('path', '/account-audit')
            ->update([
                'menu_name'  => '查賬',
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }
};
