<?php
/*
 * File: ActivityLogController.php
 * Created Time: 2024-05-29 11:27:52 am
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-05-30 4:13:38 pm
 * Modified By: WangYuQiang && WangpeiGang (719404171@qq.com>)
 * -----
 * Copyright (c) 2024 成都睿創科技有限公司
 */


namespace App\Http\Controllers\Admin;

use App\Models\ActivityLog;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
class ActivityLogController extends Controller
{
    private $ActivityLog;
    public function __construct()
    {
        $this->ActivityLog = new ActivityLog();
    }

    /**
     * 獲取員工操作日誌
     * @param Request $request
     * @return mixed
     */
    public function getActivityLogListWeb(Request $request){
        $data = $this->ActivityLog->getActivityLogListWeb($request->post());
        return $data;
    }

    /**
     * 獲取員工訂單操作日誌
     * @param Request $request
     * @return mixed
     */
    public function getOrderActivityLogListWeb(Request $request){
        $data = $this->ActivityLog->getOrderActivityLogListWeb($request->post());
        return $data;
    }

    /**
     * 刪除員工操作日誌
     * @param Request $request
     * @return array
     */
    public function deleteActivityLogWeb(Request $request){
        $activityID = $request->post('activity_id');
        $data = $this->ActivityLog->deleteActivityLogWeb($activityID);
        return $data;
    }

}
