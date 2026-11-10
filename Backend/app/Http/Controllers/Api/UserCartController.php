<?php
/*
 * File: UserUserCartController.php
 * Project: NewDemo
 * Created Time: 2024-06-24 02:07:29 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-07-30 5:07:21 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2024 成都睿創科技有限公司
 */


namespace App\Http\Controllers\Api;


use App\Models\UserCart;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserCartController extends Controller{
    
    private $UserCart;
    public function __construct()
    {
        $this->UserCart = new UserCart();
    }




    /**
     * @param Request $request
     * @return array
     * 獲取用戶購物車列表
     */
    public function getUserCart(Request $request){
        if(empty($request->post('page'))||empty($request->post('limit'))){
            return $this->parametersError(__('api.param_error'));
        }else{
            $data = $request->post();
            $user = auth('api')->user();
            $data['user_id']=$user->user_id;
            $results = $this->UserCart->getUserCart($data);
            return $results;
        }
    }


    /**
     * @param Request $request
     * @return array
     * 添加用戶購物車
     */
    public function addUserCart(Request $request){
        if( empty($request->post('product_id')) || empty($request->post('cart_num'))  ){
                
            return $this->parametersError(__('api.param_error'));
        }else{
            $data = $request->post();
            $user = auth('api')->user();
            $data['user_id']=empty($user) ? '' :  $user->user_id;
            $results = $this->UserCart->addUserCart($data);
            return $results;
        }
    }

    /**
     * @param Request $request
     * @return array
     * 編輯用戶購物車
     */
    public function editUserCart(Request $request){
        if( empty($request->post('cart_id')) || empty($request->post('cart_num'))  ){
             
            return $this->parametersError(__('api.param_error'));
        }else{
            $data = $request->post();
            $user = auth('api')->user();
            $data['user_id']=empty($user) ? '' :  $user->user_id;
            $results = $this->UserCart->editUserCart($data);
            return $results;
        }
    }


    /**
     * @param Request $request
     * @return array
     * 刪除用戶購物車
     */
    public function delUserCart(Request $request){
        if(empty($request->post('cart_id'))){
            return $this->parametersError(__('api.param_error'));
        }else{
            $data = $request->post();
            $user = auth('api')->user();
            $data['user_id']=$user->user_id;
            $results = $this->UserCart->delUserCart($data);
            return $results;
        }

    }




    

}
