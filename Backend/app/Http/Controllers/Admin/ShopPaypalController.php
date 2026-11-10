<?php
/*
 * File: ShopPaypalController.php
 * Created Time: 2024-05-29 11:27:52 am
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-06-13 2:21:14 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2024 成都睿創科技有限公司
 */


namespace App\Http\Controllers\Admin;

use App\Models\ShopPaypal;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
class ShopPaypalController extends Controller
{
    private $ShopPayPal;
    public function __construct()
    {
        $this->ShopPayPal = new ShopPaypal();
    }

    /**
     * 獲取PayPal列表
     * @param Request $request
     * @return array
     */
    public function getShopPayPalListWeb(Request $request){
        $data = $request->post();
        $datas = $this->ShopPayPal->getShopPayPalListWeb($data);
        return $datas;
    }

    /**
     * 添加/編輯PayPal信息
     * @param Request $request
     * @return array
     */
    public function handleShopPayPalWeb(Request $request){
        $data = $request->post();
        $datas = $this->ShopPayPal->handleShopPayPalWeb($data);
        return $datas;
    }

    /**
     * 刪除PayPal信息
     * @param Request $request
     * @return array
     */
    public function deleteShopPayPalWeb(Request $request){
        $paypalID = $request->post('paypal_id');
        $data = $this->ShopPayPal->deleteShopPayPalWeb($paypalID);
        return $data;
    }





}
