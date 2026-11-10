<?php
/*
 * File: CardCollectController.php
 * Project: NewDemo
 * Created Time: 2024-06-24 02:07:29 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2026-06-04 3:57:04 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2024 成都睿創科技有限公司
 */


namespace App\Http\Controllers\Api;


use App\Models\CardCollect;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CardCollectController extends Controller{
    
    private $CardCollect;
    public function __construct()
    {
        $this->CardCollect = new CardCollect();
    }



    /**
     * @param Request $request
     * @return array
     * 獲取卡包收藏列表
     */
    public function getCardCollect(Request $request){
        if(empty($request->post('page'))||empty($request->post('limit'))){
            return $this->parametersError(__('api.param_error'));
        }else{
            $data = $request->post();
            $user = auth('api')->user();
            $data['user_id']=empty($user) ? '' :  $user->user_id;
            $results = $this->CardCollect->getUsersCollect($data);
            return $results;
        }
    }

    /**
     * @param Request $request
     * @return array
     * 收藏/取消收藏卡包
     */
    public function CollectCard(Request $request){
        if(empty($request->post('card_sort_id'))){
            return $this->parametersError(__('api.param_error'));
        }else{
            $data = $request->post();
            $user = auth('api')->user();
            $data['user_id']=empty($user) ? '' :  $user->user_id;
            $results = $this->CardCollect->Collectcard($data);
            return $results;
        }
    }









    


    

}
