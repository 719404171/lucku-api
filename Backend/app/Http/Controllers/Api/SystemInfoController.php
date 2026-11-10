<?php
/*
 * File: SystemInfoController.php
 * Project: NewDemo
 * Created Time: 2024-05-29 11:27:52 am
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-07-16 9:55:40 am
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2024 成都睿創科技有限公司
 */



namespace App\Http\Controllers\Api;

use App\Models\SystemInfo;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
class SystemInfoController extends Controller{

    private $SystemInfo;
    public function __construct()
    {
        $this->SystemInfo = new SystemInfo();
    }

    /**
     * @param Request $request
     * @return array
     *系統消息
     */
    public function getSystemInfo(Request $request){
        if(empty($request->post('page'))||empty($request->post('limit'))){
            return $this->parametersError(__('api.param_error'));
        }else{
            $data = $request->post();
            $user = auth('api')->user();
            $data['user_id']=empty($user) ? '' :  $user->user_id;
            $results = $this->SystemInfo->getSystemInfo($data);
            return $results;
        }
    }



    /**
     * @param Request $request
     * @return array
     * 編輯系統消息（標記為已讀）
     */
    public function editSystemInfo(Request $request){
        $user = auth('api')->user();
        $data['user_id']=$user->user_id;
        $data['system_id']=empty($request->post('system_id')) ? 0 : $request->post('system_id');
        $results = $this->SystemInfo->editSystemInfo($data);
        return $results;
    }

    /**
     * @param Request $request
     * @return array
     * 刪除系統消息
     */
    public function DelSystemInfo(Request $request){
        if(empty($request->post('system_id'))){
            return $this->parametersError(__('api.param_error'));
        }else{
            $data = $request->post();
            $user = auth('api')->user();
            $data['user_id']=$user->user_id;
            $results = $this->SystemInfo->DelSystemInfo($data);
            return $results;
        }
    }




/**************************************************测试***********************************************************/

    /**
     * @param $data
     * @return array
     * 獲取所有系統消息
     */
    public function SystemInfoList(Request $request){
        $data=$request->post();
        $results = $this->SystemInfo->SystemInfoList($data);
        return $results;
    }






}
