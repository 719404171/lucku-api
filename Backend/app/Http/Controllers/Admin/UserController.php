<?php
/*
 * File: UserController.php
 * Created Time: 2024-05-29 11:27:52 am
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2026-06-05 9:15:16 am
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2024 成都睿創科技有限公司
 */


namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use link1st\Easemob\App\Easemob;

/**
 * Class UserController
 * @package App\Http\Controllers\Admin
 * 用戶操作和反饋信息操作
 */
class UserController extends Controller
{
    private $Users;
    public function __construct()
    {
        $this->Users = new User();
    }


    /**
     * 獲取用戶列表
     * @param Request $request
     * @return array
     */
    public function getUserListWeb(Request $request){
        $results = $this->Users->getUserListWeb($request);
        return $results;
    }

    /**
     * 新增用戶
     * @param Request $request
     * @return array
     */
    public function addUserWeb(Request $request){
        $data = $request->post();
        $token = $request->header('TOKEN');
        $datas = $this->Users->addUserWeb($data,$token);
        return $datas;
    }

    /**
     * 編輯用戶
     * @param Request $request
     * @return array
     */
    public function editUserWeb(Request $request){
        $data = $request->except(['admin_id']);
        $admin_id = $request->post('admin_id');
        $datas = $this->Users->editUserWeb($data,$admin_id);
        return $datas;
    }

    /**
     * 更改用戶密碼
     * @param Request $request
     * @return array
     */
    public function editUserPasswordWeb(Request $request){
        $password = bcrypt($request->post('user_password'));
        $datas = User::where('user_id',$request->post('user_id'))->update(['user_password'=>$password]);
        if($datas){
            return $this->success(__('web.edit_success'));
        }else{
            return $this->error(__('web.edit_fail'));
        }
    }

    /**
     * 刪除用戶
     * @param Request $request
     * @return array
     */
    public function deleteUserWeb(Request $request){
        $userID = $request->post('user_id');
        $data = $this->Users->deleteUserWeb($userID);
        return $data;
    }

    /**
     * 單個用戶：一鍵通過郵箱驗證
     */
    public function verifyUserEmailWeb(Request $request)
    {
        $user_id = (int)$request->post('user_id');
        if ($user_id <= 0) {
            return $this->error('參數錯誤');
        }

        $user = User::where('user_id', $user_id)->first();
        if (!$user) {
            return $this->error('用戶不存在');
        }

        if (empty($user->user_email)) {
            return $this->error('該用戶未綁定郵箱');
        }

        if ((int)$user->user_status === 1) {
            return $this->error('該用戶郵箱已驗證');
        }

        User::where('user_id', $user_id)->update([
            'user_status' => 1,
            'finally_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        return $this->success('已通過驗證');
    }

    /**
     * 批量：一鍵通過所有未驗證用戶
     * 只處理 user_status=0 且有 user_email 的用戶
     */
    public function verifyAllUserEmailWeb(Request $request)
    {
        $now = date('Y-m-d H:i:s');
        $count = User::where('user_status', 0)
            ->whereNotNull('user_email')
            ->where('user_email', '<>', '')
            ->update([
                'user_status' => 1,
                'finally_at'  => $now,
                'updated_at'  => $now,
            ]);

        return $this->success("已通過 {$count} 個用戶的郵箱驗證", ['count' => $count]);
    }

    /**
     * 獲取所有用戶
     * @return array
     */
    public function getAllUsersWeb(){
        $data = User::where('user_status',1)->select('user_id','user_name','user_phone','user_email')->get()->toArray();
        return $this->success(__('web.success'),$data);
    }

    /**
     * 獲取用戶消費分析
     * @param Request $request
     * @return array
     */
    public function getUserAnalysisWeb(Request $request){
        $data = $request->post();
        $language = self::getLanguageWeb();
        $datas = $this->Users->getUserAnalysisWeb($data,$language);
        return $datas;
    }
























/**********************************************************END**********************************************************************/

}
