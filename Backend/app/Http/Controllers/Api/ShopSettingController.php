<?php
/*
 * File: ShopSettingController.php
 * Project: NewDemo
 * Created Time: 2024-05-30 04:46:29 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-07-01 4:23:41 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2024 成都睿創科技有限公司
 */


namespace App\Http\Controllers\Api;

use App\Models\ShopSetting;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\CreatesApplication;

class ShopSettingController extends Controller
{
    private $ShopSetting;
    public function __construct()
    {
        $this->ShopSetting = new ShopSetting();
    }

    /**
     * @param $data
     * @return mixed
     * 所有店鋪設置信息
     */
    public function getShopSettingApi(Request $request){
        $language = self::getLanguageWeb();
        $results = $this->ShopSetting->getShopSettingApi($language);
        return $results;
    }
















}
