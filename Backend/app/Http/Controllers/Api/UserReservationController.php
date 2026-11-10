<?php
/*
 * File: UserUserReservationController.php
 * Project: NewDemo
 * Created Time: 2024-06-24 02:07:29 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-07-16 4:47:27 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2024 成都睿創科技有限公司
 */


namespace App\Http\Controllers\Api;


use App\Models\UserReservation;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserReservationController extends Controller{
    
    private $UserReservation;
    public function __construct()
    {
        $this->UserReservation = new UserReservation();
    }




    /**
     * @param Request $request
     * @return array
     * 獲取用戶和票據信息
     */
    public function getUserReservation(Request $request){
        if(empty($request->post('page'))||empty($request->post('limit'))){
            return $this->parametersError(__('api.param_error'));
        }else{
            $data = $request->post();
            $user = auth('api')->user();
            $data['user_id']=empty($user) ? '' :  $user->user_id;
            $results = $this->UserReservation->getUserReservation($data);
            return $results;
        }
    }



    

    /**
     * @param Request $request
     * @return array
     * 取票
     */
    public function addUserReservation(Request $request){
        if( empty($request->post('reservation_project_id')) ){           
            return $this->parametersError(__('api.param_error'));
        }else{
            $data = $request->post();
            $user = auth('api')->user();
            $data['user_id']=empty($user) ? '' :  $user->user_id;
            $results = $this->UserReservation->addUserReservation($data);
            return $results;
        }
    }

    /**
     * @param Request $request
     * @return array
     * 签到
     */
    public function CheckReservation(Request $request){
        if(empty($request->post('user_reservation_id'))){
            return $this->parametersError(__('api.param_error'));
        }else{
            $data = $request->post();
            $user = auth('api')->user();
            $data['user_id']=$user->user_id;
            $results = $this->UserReservation->CheckReservation($data);
            return $results;
        }
    }


    /**
     * @param Request $request
     * @return array
     * 取消排队
     */
    public function CancelReservation(Request $request){
        if(empty($request->post('user_reservation_id'))){
            return $this->parametersError(__('api.param_error'));
        }else{
            $data = $request->post();
            $user = auth('api')->user();
            $data['user_id']=$user->user_id;
            $results = $this->UserReservation->CancelReservation($data);
            return $results;
        }
    }



    

}
