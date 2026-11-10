<?php
/*
 * File: ProductSortController.php
 * Project: NewDemo
 * Created Time: 2024-05-30 04:46:29 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-07-02 9:50:08 am
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2024 成都睿創科技有限公司
 */


namespace App\Http\Controllers\Api;

use App\Models\ProductSort;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\CreatesApplication;

class ProductSortController extends Controller
{
    private $ProductSort;
    public function __construct()
    {
        $this->ProductSort = new ProductSort();
    }



    /**
     * @param $data
     * @return mixed
     * 根據服務或者產品獲得產品分类列表
     */
    public function getSortByType(Request $request){
        if(empty($request->post('page'))||empty($request->post('limit'))){
            return $this->parametersError(__('api.param_error'));
        }else{
            $data = $request->post();
            $results = $this->ProductSort->getSortByType($data);
            return $results;
        }
    }












    // /**
    //  * @param $data
    //  * @return mixed
    //  * 獲取產品分类列表
    //  */
    // public function getProductSortListApi(Request $request){
    //     if(empty($request->post('page'))||empty($request->post('limit'))){
    //         return $this->parametersError(__('api.param_error'));
    //     }else{
    //         $data = $request->post();
    //         $results = $this->ProductSort->getProductSortListApi($data);
    //         return $results;
    //     }
    // }















    


    
 }






    






    

