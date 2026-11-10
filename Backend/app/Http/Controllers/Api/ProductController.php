<?php
/*
 * File: ProductController.php
 * Project: NewDemo
 * Created Time: 2024-05-30 04:46:29 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-07-16 9:54:28 am
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2024 成都睿創科技有限公司
 */


namespace App\Http\Controllers\Api;

use App\Models\Product;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\CreatesApplication;

class ProductController extends Controller
{
    private $Product;
    public function __construct()
    {
        $this->Product = new Product();
    }

    /**
     * @param $data
     * @return mixed
     * 獲取產品列表
     */
    public function getProductListApi(Request $request){
        if(empty($request->post('page'))||empty($request->post('limit'))){
            return $this->parametersError(__('api.param_error'));
        }else{
            $data = $request->post();
            $user = auth('api')->user();
            $data['user_id']=empty($user) ? '' :  $user->user_id;
            $results = $this->Product->getProductListApi($data);
            return $results;
        }
    }






    /**
     * @param $data
     * @return mixed
     * 獲取產品详情
     */
    public function getProductDetails(Request $request){
        if(empty($request->post('product_id'))){
            return $this->parametersError(__('api.param_error'));
        }else{
            $data = $request->post();
            $user = auth('api')->user();
            $data['user_id']=empty($user) ? '' :  $user->user_id;
            $results = $this->Product->getProductDetails($data);
            return $results;
        }
    }



























    


    
 }






    






    

