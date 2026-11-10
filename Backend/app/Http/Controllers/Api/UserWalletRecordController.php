<?php
/*
 * File: UserWalletRecordController.php
 * Project: NewDemo
 * Created Time: 2024-07-03 03:33:02 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2026-06-25 4:28:22 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2024 成都睿創科技有限公司
 */



namespace App\Http\Controllers\Api;


use App\Models\UserWalletRecord;
use App\Models\Product;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserWalletRecordController extends Controller{
    
    private $UserWalletRecord;
    private $Product;
    public function __construct()
    {
        $this->UserWalletRecord = new UserWalletRecord();
        $this->Product = new Product();
    }



    /**
     * @param Request $request
     * @return array
     * 獲取用戶錢包明細
     */
    public function getUserWallet(Request $request){
        if(empty($request->post('page'))||empty($request->post('limit'))){
            return $this->parametersError(__('api.param_error'));
        }else{
            $data = $request->post();
            $user = auth('api')->user();
            $data['user_id']=empty($user) ? '' :  $user->user_id;
            $data['type'] = empty($request->post('type')) ? 1 : $request->post('type') * 1;
            $data['wallet_type'] = empty($request->post('wallet_type')) ? 0 : $request->post('wallet_type') * 1;
            $results = $this->UserWalletRecord->getUserWallet($data);
            return $results;
        }
    }









    



    


    

}
