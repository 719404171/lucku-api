<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/7/3
 * Time: 14:39
 */

namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use App\Models\Push;
use Illuminate\Http\Request;

class PushController extends Controller
{
    protected $Push;

    public function __construct()
    {
        $this->Push = new Push();
    }

    /**
     * 推送消息
     * @param Request $request
     * @return array
     */
    public function pushMessageWeb(Request $request){
        $data = $request->post();
        $datas = $this->Push->pushMessageWeb($data);
        return $datas;
    }

    /**
     * 獲取推送消息列表
     * @param Request $request
     * @return array
     */
    public function getPushMessageListWeb(Request $request){
        $data = $request->post();
        $datas = $this->Push->getPushMessageListWeb($data);
        return $datas;
    }

    /**
     * 刪除推送消息
     * @param Request $request
     * @return array
     */
    public function deletePushMessageWeb(Request $request){
        $pushID = $request->post('push_id');
        $data = $this->Push->deletePushMessageWeb($pushID);
        return $data;
    }

}
