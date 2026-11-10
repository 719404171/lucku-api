<?php
/*
 * File: ShopStripeController.php
 * Created Time: 2024-05-29 11:27:52 am
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-06-13 2:21:14 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2024 成都睿創科技有限公司
 */


namespace App\Http\Controllers\Admin;

use App\Models\ShopStripe;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
class ShopStripeController extends Controller
{
    private $ShopStripe;
    public function __construct()
    {
        $this->ShopStripe = new ShopStripe();
    }

    /**
     * 獲取PayPal列表
     * @param Request $request
     * @return array
     */
    public function getShopStripeListWeb(Request $request){
        $data = $request->post();
        $datas = $this->ShopStripe->getShopStripeListWeb($data);
        return $datas;
    }

    /**
     * 添加/編輯PayPal信息
     * @param Request $request
     * @return array
     */
    public function handleShopStripeWeb(Request $request){
        $data = $request->post();
        $datas = $this->ShopStripe->handleShopStripeWeb($data);
        return $datas;
    }

    /**
     * 刪除PayPal信息
     * @param Request $request
     * @return array
     */
    public function deleteShopStripeWeb(Request $request){
        $paypalID = $request->post('stripe_id');
        $data = $this->ShopStripe->deleteShopStripeWeb($paypalID);
        return $data;
    }





}
