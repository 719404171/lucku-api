<?php
/*
 * File: 2026_06_25_000005_update_invitation_code_menu_name.php
 * Project: NewDemo
 * Created Time: 2026-06-25 05:46:13 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2026-06-25 5:59:38 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2026 成都睿創科技有限公司
 */
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class UpdateInvitationCodeMenuName extends Migration
{
    public function up()
    {
        // 處理舊路徑（shop/invitation-code）或新路徑（/invitation-code）
        $record = DB::table('menu')
            ->where(function ($q) {
                $q->where('path', 'shop/invitation-code')
                  ->orWhere('path', '/invitation-code');
            })
            ->first();

        if ($record) {
            DB::table('menu')
                ->where('menu_id', $record->menu_id)
                ->update([
                    'menu_name' => '邀請碼管理',
                    'en_menu_name' => 'Invitation Codes',
                    'path' => '/invitation-code',
                ]);
        } else {
            $parent = DB::table('menu')->where('path', 'shop')->first();
            $parentId = $parent ? $parent->menu_id : 0;

            DB::table('menu')->insert([
                'menu_name' => '邀請碼管理',
                'en_menu_name' => 'Invitation Codes',
                'parent' => $parentId,
                'icon' => 'el-icon-paperclip',
                'path' => '/invitation-code',
                'status' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public function down()
    {
        // 不做刪除操作，避免誤刪
    }
}
