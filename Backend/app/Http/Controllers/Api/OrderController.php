<?php
/*
 * File: OrderController.php
 * Project: NewDemo
 * Created Time: 2024-07-12 03:00:30 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-07-17 3:16:34 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2024 成都睿創科技有限公司
 */
namespace App\Http\Controllers\Api;


use App\Models\Order;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OrderController extends Controller{
    
    private $Order;
    public function __construct()
    {
        $this->Order = new Order();
    }



    /**
     * @param Request $request
     * @return array
     * 獲取订单列表
     */
    public function getOrderListApi(Request $request){
        if(empty($request->post('page'))||empty($request->post('limit'))){
            return $this->parametersError(__('api.param_error'));
        }else{
            $data = $request->post();
            $user = auth('api')->user();
            $data['user_id']=$user->user_id;
            $results = $this->Order->getOrderListApi($data);
            return $results;
        }
    }

    /**
     * @param Request $request
     * @return array
     * 獲取訂單詳情
     */
    public function getOrderDetailsApi(Request $request){
        if(empty($request->post('order_id'))){
            return $this->parametersError(__('api.param_error'));
        }else{
            $data = $request->post();
            $user = auth('api')->user();
            $data['user_id']=$user->user_id;
            $results = $this->Order->getOrderDetailsApi($data);
            return $results;
        }
    }

    /**
     * @param Request $request
     * @return array
     * 檢查并取消用戶超時訂單
     */
    public function CheckOrderExpired(Request $request){
        $data = $request->post();
        $user = auth('api')->user();
        $data['user_id']=$user->user_id;
        $results = $this->Order->CheckOrderExpired($data);
        return $results;
    }

    /**
     * @param Request $request
     * @return array
     * 确认收貨
     */
    public function ConfirmReceipt(Request $request){
        if(empty($request->post('order_id'))){
            return $this->parametersError(__('api.param_error'));
        }else{
            $data = $request->post();
            $user = auth('api')->user();
            $data['user_id']=$user->user_id;
            $results = $this->Order->ConfirmReceipt($data);
            return $results;
        }
    }


    
    /**
     * @param Request $request
     * @return array
     * 刪除訂單
     */
    public function deleteOrderApi(Request $request){
        if(empty($request->post('order_id'))){
            return $this->parametersError(__('api.param_error'));
        }else{
            $data = $request->post();
            $user = auth('api')->user();
            $data['user_id']=empty($user) ? '' :  $user->user_id;
            $results = $this->Order->deleteOrderApi($data);
            return $results;
        }
    }

}
