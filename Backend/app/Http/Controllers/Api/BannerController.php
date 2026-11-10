<?php
/*
 * File: BannerController.php
 * Project: NewDemo
 * Created Time: 2024-05-30 03:04:24
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-06-25 3:20:03 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2024 成都睿創科技有限公司
 */


namespace App\Http\Controllers\Api;

use App\Models\Banner;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\CreatesApplication;

class BannerController extends Controller
{
    private $Banner;
    public function __construct()
    {
        $this->Banner = new Banner();
    }


    /**
     * @param Request $request
     * @return array
     * 獲取Banner列表
     */
    public function getBannerApi(Request $request){
        if(empty($request->post('page'))||empty($request->post('limit'))){
            return $this->parametersError(__('api.param_error'));
        }else{
            $data = $request->post();
            $results = $this->Banner->getBannerApi($data);
            return $results;
        }


    }

























}

