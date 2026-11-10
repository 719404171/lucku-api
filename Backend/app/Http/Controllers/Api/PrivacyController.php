<?php
/*
 * File: PrivacyController.php
 * Project: NewDemo
 * Created Time: 2024-05-30 03:38:28 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-05-30 4:38:14 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2024 成都睿創科技有限公司
 */



namespace App\Http\Controllers\Api;

use App\Models\Privacy;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
class PrivacyController extends Controller{
    
    private $Privacy;
    public function __construct()
    {
        $this->Privacy = new Privacy();
    }

    /**
     * @param Request $request
     * @return array
     *獲取隱私協議
     */
    public function getPrivacyApi(Request $request){
        if(empty($request->post('page'))||empty($request->post('limit'))){
            return $this->parametersError();
        }else{
            $info['page']=$request->post('page');
            $info['limit']=$request->post('limit');
            $results = $this->Privacy->getPrivacyApi($info);
            return $results;
        }
    }





/**************************************************测试***********************************************************/



}
