<?php
/*
 * File: UserIntegralRecordController.php
 * Project: NewDemo
 * Created Time: 2024-07-03 03:33:02 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-07-16 9:56:27 am
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2024 成都睿創科技有限公司
 */



namespace App\Http\Controllers\Api;


use App\Models\UserIntegralRecord;
use App\Models\Product;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserIntegralRecordController extends Controller{
    
    private $UserIntegralRecord;
    private $Product;
    public function __construct()
    {
        $this->UserIntegralRecord = new UserIntegralRecord();
        $this->Product = new Product();
    }



    /**
     * @param Request $request
     * @return array
     * 獲取用戶積分明細
     */
    public function getUserIntegral(Request $request){
        if(empty($request->post('page'))||empty($request->post('limit'))){
            return $this->parametersError(__('api.param_error'));
        }else{
            $data = $request->post();
            $user = auth('api')->user();
            $data['user_id']=empty($user) ? '' :  $user->user_id;
            $data['integral_type'] = empty($request->post('integral_type')) ? 0 : $request->post('integral_type');
            $results = $this->UserIntegralRecord->getUserIntegral($data);
            return $results;
        }
    }









    



    


    

}
