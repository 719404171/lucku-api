<?php
/*
 * File: UserUserCollectController.php
 * Project: NewDemo
 * Created Time: 2024-06-24 02:07:29 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-07-24 9:45:29 am
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2024 成都睿創科技有限公司
 */


namespace App\Http\Controllers\Api;


use App\Models\UserCollect;
use App\Models\Product;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserCollectController extends Controller{
    
    private $UserCollect;
    private $Product;
    public function __construct()
    {
        $this->UserCollect = new UserCollect();
        $this->Product = new Product();
    }



    /**
     * @param Request $request
     * @return array
     * 獲取用戶收藏
     */
    public function getUsersCollect(Request $request){
        if(empty($request->post('page'))||empty($request->post('limit'))){
            return $this->parametersError(__('api.param_error'));
        }else{
            $data = $request->post();
            $user = auth('api')->user();
            $data['user_id']=empty($user) ? '' :  $user->user_id;
            $data['collect_type'] = empty($request->post('collect_type')) ? '' : $request->post('collect_type');
            $results = $this->UserCollect->getUsersCollect($data);
            return $results;
        }
    }




    /**
     * @param Request $request
     * @return array
     * 收藏/取消收藏商品
     */
    public function CollectProduct(Request $request){
        if(empty($request->post('product_id'))){
            return $this->parametersError(__('api.param_error'));
        }else{
            $info = $request->post();
            $user = auth('api')->user();
            $data['user_id']=empty($user) ? '' :  $user->user_id;
            foreach ($info['product_id'] as $key => $value) {
                $data['product_id'] = $info['product_id'][$key];
                $data['product_type'] = $this->Product::where('product_id',$data['product_id'])->select('product_type')->first()['product_type'];
                $results = $this->UserCollect->CollectProduct($data);
            }
            return $results;
        }
    }









    


    

}
