<?php
/*
 * File: ShopFAQController.php
 * Project: NewDemo
 * Created Time: 2024-06-24 02:07:29 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-06-25 3:19:02 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2024 成都睿創科技有限公司
 */


namespace App\Http\Controllers\Api;


use App\Models\ShopFAQ;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
class ShopFAQController extends Controller
{
    private $ShopFAQ;
    public function __construct()
    {
        $this->ShopFAQ = new ShopFAQ();
    }

    /**
     * @param Request $request
     * @return array
     * 獲取獲取问答列表
     */
    public function getShopFAQApi(Request $request){
        if(empty($request->post('page'))||empty($request->post('limit'))){
            return $this->parametersError(__('api.param_error'));
        }else{
            $data = $request->post();
            $results = $this->ShopFAQ->getShopFAQApi($data);
            return $results;
        }
    }









    

}
