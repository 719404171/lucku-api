<?php
/*
 * File: CardController.php
 * Project: NewDemo
 * Created Time: 2024-05-30 04:46:29 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2026-09-09 6:00:12 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2024 成都睿創科技有限公司
 */


namespace App\Http\Controllers\Api;

use App\Models\Card;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\CreatesApplication;

class CardController extends Controller
{
    private $Card;
    public function __construct()
    {
        $this->Card = new Card();
    }
    
    /**
     * 抽取卡片並創建訂單（合併接口，僅錢包支付）
     * 參數：card_sort_id、count
     */
    public function confirmDrawOrder(Request $request){
        if(empty($request->post('card_sort_id'))||empty($request->post('count'))){
            return $this->parametersError(__('api.param_error'));
        }
        $data = $request->post();
        $user = auth('api')->user();
        if (empty($user)) {
            return $this->error(__('api.no_login'));
        }
        $data['user_id']= $user->user_id;
        return $this->Card->confirmDrawOrder($data);
    }

    /**
     * @param $data
     * @return mixed
     * 根據卡包ID獲取卡片列表
     */
    public function getCardsBySort(Request $request){
        if(empty($request->post('card_sort_id'))||empty($request->post('page'))||empty($request->post('limit'))){
            return $this->parametersError(__('api.param_error'));
        }else{
            $data = $request->post();
            $results = $this->Card->getCardsBySort($data);
            return $results;
        }
    }

    /**
     * 獲取卡片的抽取人信息
     */
    public function getCardDrawerInfo(Request $request){
        $card_id = $request->post('card_id');
        if (empty($card_id)) {
            return $this->parametersError(__('api.param_error'));
        }
        $info = $this->Card->getCardDrawerInfo($card_id);
        if ($info) {
            return $this->success(__('api.list_success'), $info);
        }
        return $this->success(__('api.list_null'), null);
    }

    /**
     * 查詢抽卡建單任務狀態（配合異步建單輪詢）
     * 參數：task_id
     * status：0 建單中 / 1 已建單（可刷新訂單列表）/ 2 待人工
     */
    public function getDrawTaskStatus(Request $request){
        $taskId = (int)$request->post('task_id');
        if ($taskId <= 0) {
            return $this->parametersError(__('api.param_error'));
        }
        $user = auth('api')->user();
        if (empty($user)) {
            return $this->error(__('api.no_login'));
        }
        $task = DB::table('card_draw_tasks')
            ->where('task_id', $taskId)
            ->where('user_id', $user->user_id)
            ->first();
        if (empty($task)) {
            return $this->error(__('api.param_error'));
        }
        $cards = json_decode($task->cards_json, true);
        if (!is_array($cards)) {
            $cards = [];
        }
        return $this->success(__('api.list_success'), [
            'task_id'    => (int)$task->task_id,
            'status'     => (int)$task->status,
            'cards'      => $cards,
            'total_cost' => (float)$task->total_cost
        ]);
    }














    


    


    
 }






    






    

