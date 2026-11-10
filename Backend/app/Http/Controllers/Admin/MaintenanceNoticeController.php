<?php
/*
 * 维护公告管理控制器
 * 后台店铺管理 → 维护公告 页面
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\MaintenanceNoticeHeader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MaintenanceNoticeController extends Controller
{
    /**
     * 获取维护公告设置
     */
    public function getMaintenanceNoticeWeb()
    {
        $data = DB::table('maintenance_notice')->where('id', 1)->first();
        if (empty($data)) {
            $data = ['maintenance_enabled' => 0, 'notice_content' => '', 'en_notice_content' => ''];
        }
        return $this->success(__('web.success'), (array)$data);
    }

    /**
     * 编辑维护公告设置
     */
    public function editMaintenanceNoticeWeb(Request $request)
    {
        $maintenanceEnabled = (int)$request->post('maintenance_enabled', 0);
        $noticeContent      = $request->post('notice_content', '');
        $enNoticeContent    = $request->post('en_notice_content', '');

        $updated = DB::table('maintenance_notice')->where('id', 1)->update([
            'maintenance_enabled' => $maintenanceEnabled,
            'notice_content'      => $noticeContent,
            'en_notice_content'   => $enNoticeContent,
            'updated_at'          => date('Y-m-d H:i:s'),
        ]);

        if ($updated !== false) {
            // 清除维护状态缓存，让 APP 端响应头即时反映最新开关
            MaintenanceNoticeHeader::forget();
            return $this->success(__('web.success'));
        }
        return $this->error(__('web.fail'));
    }
}
