<?php
/*
 * File: UserUserMessageController.php
 * Project: NewDemo
 * Created Time: 2024-06-24 02:07:29 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-07-16 9:56:35 am
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2024 成都睿創科技有限公司
 */


namespace App\Http\Controllers\Api;


use App\Models\UserMessage;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserMessageController extends Controller{
    
    private $UserMessage;
    public function __construct()
    {
        $this->UserMessage = new UserMessage();
    }





    /**
     * @param Request $request
     * @return array
     * 添加用戶反饋
     */
    public function addUserMessage(Request $request){
        if( empty($request->post('message_content'))  ){  
            return $this->parametersError(__('api.param_error'));
        }else{
            $data = $request->post();
            $user = auth('api')->user();
            $data['user_id']=empty($user) ? '' :  $user->user_id;
            $results = $this->UserMessage->addUserMessage($data);
            return $results;
        }
    }







    

}
