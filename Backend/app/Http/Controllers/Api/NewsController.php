<?php
/*
 * File: NewsController.php
 * Project: NewDemo
 * Created Time: 2024-06-24 02:07:29 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-06-25 3:19:45 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2024 成都睿創科技有限公司
 */


namespace App\Http\Controllers\Api;


use App\Models\News;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
class NewsController extends Controller
{
    private $News;
    public function __construct()
    {
        $this->News = new News();
    }

    /**
     * @param Request $request
     * @return array
     * 獲取獲取最新資訊列表
     */
    public function getNewsApi(Request $request){
        if(empty($request->post('page'))||empty($request->post('limit'))){
            return $this->parametersError(__('api.param_error'));
        }else{
            $data = $request->post();
            $results = $this->News->getNewsApi($data);
            return $results;
        }
    }


    /**
     * @param Request $request
     * @return array
     * 獲取獲取最新資訊詳情
     */
    public function getNewsDetails(Request $request){
        if(empty($request->post('news_id'))){
            return $this->parametersError(__('api.param_error'));
        }else{
            $data = $request->post();
            $results = $this->News->getNewsDetails($data);
            return $results;
        }
        
    }






    

}
