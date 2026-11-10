<?php
/*
 * File: CardOrderDetailsController.php
 * Project: NewDemo
 * Created Time: 2024-07-12 03:00:30 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2026-06-18 11:20:51 am
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2024 成都睿創科技有限公司
 */
namespace App\Http\Controllers\Api;


use App\Models\CardOrderDetails;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CardOrderDetailsController extends Controller{
    
    private $CardOrderDetails;
    public function __construct()
    {
        $this->CardOrderDetails = new CardOrderDetails();
    }


    /**
     * @param Request $request
     * @return array
     * 獲取用戶背包（訂單詳情列表）
     */
    public function getUserBackpack(Request $request){
        if(empty($request->post('page'))||empty($request->post('limit'))){
            return $this->parametersError(__('api.param_error'));
        }else{
            $data = $request->post();
            $user = auth('api')->user();
            $data['user_id']=$user->user_id;
            $results = $this->CardOrderDetails->getUserBackpack($data);
            return $results;
        }
    }

    /**
     * @param Request $request
     * @return array
     * 處理卡包卡片（兌換積分或發貨）
     */
    public function handleCardPack(Request $request){
        if(empty($request->post('card_order_details_id')) || empty($request->post('type'))){
            return $this->parametersError(__('api.param_error'));
        }else{
            $data = $request->post();
            $user = auth('api')->user();
            $data['user_id']=$user->user_id;
            $results = $this->CardOrderDetails->handleCardPack($data);
            return $results;
        }
    }








    

}
