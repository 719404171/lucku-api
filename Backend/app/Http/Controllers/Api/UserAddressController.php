<?php
/*
 * File: UserUserAddressController.php
 * Project: NewDemo
 * Created Time: 2024-06-24 02:07:29 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-07-16 9:55:45 am
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2024 成都睿創科技有限公司
 */


namespace App\Http\Controllers\Api;


use App\Models\UserAddress;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserAddressController extends Controller{
    
    private $UserAddress;
    public function __construct()
    {
        $this->UserAddress = new UserAddress();
    }




    /**
     * @param Request $request
     * @return array
     * 獲取收貨地址列表
     */
    public function getUserAddress(Request $request){
        if(empty($request->post('page'))||empty($request->post('limit'))){
            return $this->parametersError(__('api.param_error'));
        }else{
            $data = $request->post();
            $user = auth('api')->user();
            $data['user_id']=empty($user) ? '' :  $user->user_id;
            $results = $this->UserAddress->getUserAddress($data);
            return $results;
        }
    }


    /**
     * @param Request $request
     * @return array
     * 添加收貨地址
     */
    public function addUserAddress(Request $request){
        if( empty($request->post('address_name')) || empty($request->post('address_phone')) ||
            empty($request->post('address_address'))  ){
                
            return $this->parametersError(__('api.param_error'));
        }else{
            $data = $request->post();
            $user = auth('api')->user();
            $data['user_id']=$user->user_id;
            $results = $this->UserAddress->addUserAddress($data);
            return $results;
        }
    }

    /**
     * @param Request $request
     * @return array
     * 編輯收貨地址
     */
    public function editUserAddress(Request $request){
        if(empty($request->post('address_id'))){
            return $this->parametersError(__('api.param_error'));
        }else{
            $data = $request->post();
            $user = auth('api')->user();
            $data['user_id']=$user->user_id;
            $results = $this->UserAddress->editUserAddress($data);
            return $results;
        }
    }


    /**
     * @param Request $request
     * @return array
     * 刪除收貨地址
     */
    public function delUserAddress(Request $request){
        if(empty($request->post('address_id'))){
            return $this->parametersError(__('api.param_error'));
        }else{
            $data = $request->post();
            $user = auth('api')->user();
            $data['user_id']=$user->user_id;
            $results = $this->UserAddress->delUserAddress($data);
            return $results;
        }

    }




    

}
