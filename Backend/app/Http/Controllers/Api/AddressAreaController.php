<?php
/*
 * File: AddressAreaController.php
 * Project: NewDemo
 * Created Time: 2024-06-24 02:07:29 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-09-06 4:58:55 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2024 成都睿創科技有限公司
 */


namespace App\Http\Controllers\Api;


use App\Models\AddressArea;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
class AddressAreaController extends Controller
{
    private $AddressArea;
    public function __construct()
    {
        $this->AddressArea = new AddressArea();
    }

    /**
     * @param Request $request
     * @return array
     *  獲取区域选择列表
     */
    public function getAddressAreaApi(Request $request){
        $results = $this->AddressArea->getAddressAreaApi();
        return $results;
    }









    

}
