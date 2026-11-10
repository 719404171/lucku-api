<?php
/*
 * File: CardSortController.php
 * Project: NewDemo
 * Created Time: 2024-05-30 04:46:29 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2026-06-18 3:00:18 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2024 成都睿創科技有限公司
 */


namespace App\Http\Controllers\Api;

use App\Models\CardSort;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\CreatesApplication;

class CardSortController extends Controller
{
    private $CardSort;
    public function __construct()
    {
        $this->CardSort = new CardSort();
    }



    /**
     * @param $data
     * @return mixed
     * 根據服務或者卡片獲得卡片分类列表
     */
    public function getCardSort(Request $request){
        if(empty($request->post('page'))||empty($request->post('limit'))){
            return $this->parametersError(__('api.param_error'));
        }else{
            $data = $request->post();
            $user = auth('api')->user();
            $data['user_id'] = $user->user_id ?? 0;
            $results = $this->CardSort->getCardSort($data);
            return $results;
        }
    }

    /**
     * @param $data
     * @return mixed
     * 通過關鍵字搜索卡包，返回相關卡包
     */
    public function searchCardsByKeyword(Request $request){
        if(empty($request->post('keyword'))||empty($request->post('page'))||empty($request->post('limit'))){
            return $this->parametersError(__('api.param_error'));
        }else{
            $data = $request->post();
            $user = auth('api')->user();
            $data['user_id'] = $user->user_id ?? 0;
            $results = $this->CardSort->searchCardsByKeyword($data);
            return $results;
        }
    }

    /**
     * 獲取全部卡池品牌（不重複），供 App 依品牌篩選卡池
     */
    public function getCardBrands(Request $request){
        return $this->CardSort->getCardBrands();
    }

    /**
     * 獲取全部卡池標籤（不重複），供 App 依標籤篩選卡池
     */
    public function getCardTags(Request $request){
        return $this->CardSort->getCardTags();
    }

    /**
     * 通過品牌查找擁有該品牌卡牌的卡池（brand 為空則返回全部）
     */
    public function getCardSortByBrand(Request $request){
        $data = $request->post();
        $user = auth('api')->user();
        $data['user_id'] = $user->user_id ?? 0;
        $results = $this->CardSort->getCardSortByBrand($data);
        return $results;
    }
}






    






    

