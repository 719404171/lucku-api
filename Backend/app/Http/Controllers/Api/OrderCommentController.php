<?php
/*
 * File: OrderCommentController.php
 * Project: NewDemo
 * Created Time: 2024-07-15 03:54:02 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-07-16 9:55:11 am
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2024 成都睿創科技有限公司
 */


namespace App\Http\Controllers\Api;


use App\Models\OrderComment;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OrderCommentController extends Controller{

    private $OrderComment;
    public function __construct()
    {
        $this->OrderComment = new OrderComment();
    }


    /**
     * @param Request $request
     * @return array
     * 發佈評論
     */
    public function PublishComment(Request $request){
        if(empty($request->post('product_id')) || empty($request->post('details_id'))
                || empty($request->post('comment_text'))||empty($request->post('comment_score')) ){
            return $this->parametersError(__('api.param_error'));
        }else{
            $data = $request->post();
            $user = auth('api')->user();
            $data['user_id']=empty($user) ? '' :  $user->user_id;
            $results = $this->OrderComment->PublishComment($data);
            return $results;
        }
    }



    /**
     * @param Request $request
     * @return array
     * 獲取商品评论
     */
    public function getProductComment(Request $request){
        if(empty($request->post('page'))||empty($request->post('limit'))||empty($request->post('product_id'))){
            return $this->parametersError(__('api.param_error'));
        }else{
            $data = $request->post();
            $user = auth('api')->user();
            $data['user_id']=empty($user) ? '' :  $user->user_id;
            $results = $this->OrderComment->getProductComment($data);
            return $results;
        }
    }

    /**
     * @param Request $request
     * @return array
     * 獲取我的所有评论
     */
    public function getMyComment(Request $request){
        if(empty($request->post('page'))||empty($request->post('limit'))){
            return $this->parametersError(__('api.param_error'));
        }else{
            $data = $request->post();
            $user = auth('api')->user();
            $data['user_id']=empty($user) ? '' :  $user->user_id;
            $results = $this->OrderComment->getMyComment($data);
            return $results;
        }
    }






}
