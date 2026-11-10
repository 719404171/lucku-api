<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/7/4
 * Time: 13:55
 */

namespace App\Http\Controllers\Admin;

use App\Models\AdminLog;
use Illuminate\Http\Request;

use App\Http\Controllers\Controller;

class AdminLogController extends Controller
{
    protected $AdminLog;

    public function __construct()
    {
        $this->AdminLog = new AdminLog();
    }

    /**
     * 獲取管理員登錄日誌
     * @param Request $request
     * @return array
     */
    public function getAdminLogListWeb(Request $request){
        $data = $request->post();
        $datas = $this->AdminLog->getAdminLogListWeb($data);
        return $datas;
    }

}
