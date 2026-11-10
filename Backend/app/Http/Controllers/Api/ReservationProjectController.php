<?php
/*
 * File: ReservationProjectController.php
 * Project: NewDemo
 * Created Time: 2024-07-15 04:30:38 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-07-16 3:02:55 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2024 成都睿創科技有限公司
 */




namespace App\Http\Controllers\Api;


use App\Models\ReservationProject;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReservationProjectController extends Controller{
    
    private $ReservationProject;
    public function __construct()
    {
        $this->ReservationProject = new ReservationProject();
    }


    /**
     * @param Request $request
     * @return array
     * 獲取排队项目列表
     */
    public function getReservationProject(Request $request){
        if(empty($request->post('page'))||empty($request->post('limit'))){
            return $this->parametersError(__('api.param_error'));
        }else{
            $data = $request->post();
            $data = $request->post();
            $user = auth('api')->user();
            $data['user_id']=empty($user) ? '' :  $user->user_id;
            $results = $this->ReservationProject->getReservationProject($data);
            return $results;
        }
    }



    

}
