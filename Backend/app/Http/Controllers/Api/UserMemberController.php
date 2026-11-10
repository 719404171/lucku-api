<?php
/*
 * File: UserMemberController.php
 * Project: NewDemo
 * Created Time: 2024-05-30 10:32:20 am
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-06-25 4:28:47 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2024 成都睿創科技有限公司
 */



namespace App\Http\Controllers\Api;

use App\Models\UserMember;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\CreatesApplication;

class UserMemberController extends Controller{

    
    private $UserMember;
    public function __construct()
    {
        $this->UserMember = new UserMember();
    }


    /**
     * @param Request $request
     * @return array
     * 獲取會員等級信息
     */
    public function getUserMemberrApi(Request $request){
        if(empty($request->post())){
            return $this->parametersError(__('api.param_error'));
        }else{
            $data = $request->post();
            $results = $this->UserMember->getUserMemberrApi($data);
            return $results;
        }
    }

    /**
     * @param Request $request
     * @return array
     * 獲取所有會員等級信息（不分頁）
     */
    public function getMemberApi(Request $request){
        $results = $this->UserMember->getMemberApi();
        return $results;
    }



}
