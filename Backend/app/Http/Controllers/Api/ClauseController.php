<?php
/*
 * File: ClauseController.php
 * Created Time: 2024-05-29 1:45:26 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-06-25 3:20:20 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2024 成都睿創科技有限公司
 */


namespace App\Http\Controllers\Api;

use App\Models\Clause;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
class ClauseController extends Controller{
    
    private $Clause;
    public function __construct()
    {
        $this->Clause = new Clause();
    }

    /**
     * @param Request $request
     * @return array
     *獲取用戶協議
     */
    public function getClauseApi(Request $request){
        if(empty($request->post('page'))||empty($request->post('limit'))){
            return $this->parametersError();
        }else{
            $data = $request->post();
            $results = $this->Clause->getClauseApi($data);
            return $results;
        }
    }





/**************************************************测试***********************************************************/



}
