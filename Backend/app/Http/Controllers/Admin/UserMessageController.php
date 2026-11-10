<?php
/*
 * File: UserMessageController.php
 * Project: NewDemo
 * Created Time: 2024-07-03 05:02:02 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-07-09 11:20:30 am
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2024 成都睿創科技有限公司
 */



namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use App\Models\UserMessage;
use Illuminate\Http\Request;

class UserMessageController extends Controller
{
    protected $UserMessage;

    public function __construct()
    {
        $this->UserMessage = new UserMessage();
    }

    /**
     * 获取留言列表
     * @param Request $request
     * @return array
     */
    public function getMessageListWeb(Request $request){
        $data = $request->post();
        $datas = $this->UserMessage->getMessageListWeb($data);
        return $datas;
    }

    /**
     * 刪除留言信息
     * @param Request $request
     * @return array
     */
    public function deleteMessageWeb(Request $request){
        $messageID = $request->post('message_id');
        $data = $this->UserMessage->deleteMessageWeb($messageID);
        return $data;
    }

}
