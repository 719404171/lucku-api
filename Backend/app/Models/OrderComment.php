<?php
/*
 * File: OrderComment.php
 * Project: NewDemo
 * Created Time: 2024-07-11 09:52:53 am
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-07-18 2:45:59 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2024 成都睿創科技有限公司
 */

namespace App\Models;

use DateTimeInterface;
use App\Models\Base;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;

/**
 * App\Models\News
 */
class OrderComment extends Base
{
    protected $table = 'order_comment';
    use Notifiable;
    public $timestamps = true;

    /**
     * 为数组 / JSON 序列化准备日期。
     */
    protected function serializeDate(DateTimeInterface $date){
        return $date->format($this->dateFormat ?: 'Y-m-d H:i:s');
    }



    /**
     * 函數構造
     */
    public function __construct(){
        $this->language = app('global_language') == "en" ? "en_" : "";
        $this->AppDetail = ['comment_id','user_id','product_id','details_id','comment_img','comment_text','comment_score','comment_status','created_at'];
    }

/*********************************************************全局作用域注册**********************************************************************/
    protected static function boot(){
       parent::boot();
	   static::addGlobalScope('order_comment', function (Builder $builder) {
		  return $builder->where('order_comment.comment_status', '<>', -1);
	   });
    }

/*********************************************************admin**********************************************************************/


    /**
     * 獲取評論列表
     * @param $data
     * @param $language
     * @return mixed
     */
    public function getOrderCommentListWeb($data,$language){
        $limit = $data['limit'];
        $page =  ( $data['page'] - 1 ) * $limit;
        $time = [];
        $where[] = array('order_comment.comment_status', '<>', -1);
        $where[] = array('users.user_status', '<>', -1);
        if (!empty($data['comment_text'])) {
            $where[] = array('order_comment.comment_text', 'like', '%' . $data['comment_text'] . '%');
        }
        if (!empty($data['product_name'])) {
            $where[] = array('product.'.$language.'product_name', 'like', '%' . $data['product_name'] . '%');
        }
        if(!empty($data['comment_score'])){
            $where['order_comment.comment_score'] = $data['comment_score'];
        }
        if(!empty($data['time'])){
            $time['start_time'] = $data['time'][0];
            $time['end_time'] = $data['time'][1];
        }
        $datas['data'] = $this->join('users','users.user_id','=','order_comment.user_id')
            ->join('product','product.product_id','=','order_comment.product_id')
            ->where($where)
            ->where(function ($query) use ($time){
                if(!empty($time) && $time['start_time'] != ''){
                    return $query->whereBetween('order_comment.created_at',$time);
                }
            })
            ->select('order_comment.*','users.user_name','product.'.$language.'product_name')
            ->offset($page)->limit($limit)
            ->orderBy('order_comment.created_at', 'desc')
            ->get()->toArray();
        $datas['count'] = $this->join('users','users.user_id','=','order_comment.user_id')
            ->join('product','product.product_id','=','order_comment.product_id')
            ->where($where)
            ->where(function ($query) use ($time){
                if(!empty($time) && $time['start_time'] != ''){
                    return $query->whereBetween('order_comment.created_at',$time);
                }
            })->count();
        return $datas;
    }

    /**
     * 刪除評論
     * @param $commentID
     * @return array
     */
    public function deleteOrderCommentWeb($commentID){
        $data = self::softDelete('order_comment','comment_id',$commentID,'comment_status',-1);
        if($data){
            return $this->success(__('web.delete_success'));
        }else{
            return $this->error(__('web.delete_fail'));
        }
    }

/**********************************************************api**********************************************************************/

    /**
     * @param $token
     * @param $data
     * @return mixed
     * 發佈評論
     */
    public function PublishComment($data){
        DB::beginTransaction();
        try {
            // 校驗該訂單明細屬於當前用戶，避免評論/改物流狀態越權（details_id 可被枚舉）
            $detail = DB::table('order_details')
                ->where('details_id', $data['details_id'])
                ->where('user_id', $data['user_id'])
                ->first();
            if (empty($detail)) {
                DB::rollBack();
                return $this->error(__('api.param_error'), '訂單明細不存在或無權操作');
            }

            $info = [
                'user_id' => $data['user_id'],
                'product_id' => $data['product_id'],
                'details_id' => $data['details_id'],
                'comment_img' => empty($data['comment_img']) ?  '' : $data['comment_img'],
                'comment_text' => $data['comment_text'],
                'comment_score' => $data['comment_score'],
                'comment_status' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            $effects = $this->insert($info);
            if(!empty($effects)){
                // 用 DB::table 直接更新，避免 Eloquent 作用域污染；order_logistics 無 details_id，改用 order_id 定位
                DB::table('order_details')
                    ->where('details_id', $data['details_id'])
                    ->where('user_id', $data['user_id'])
                    ->update(['logistics_status' => 3]);
                DB::table('order_logistics')
                    ->where('order_id', $detail->order_id)
                    ->where('user_id', $data['user_id'])
                    ->update(['logistics_status' => 3]);
                DB::commit();
                return $this->success( __('api.add_success'));
            }else{
                DB::rollBack();
                return $this->error( __('api.add_fail'));
            }
        }catch (\Exception $e) {
            DB::rollBack();
            return $this->error( __('api.add_fail') , $e->getMessage());
        }
    }



    /**
     * @param $data
     * @return mixed
     * 獲取商品评论
     */
    public function getProductComment($data){
        $limit = empty($data['limit']) ? 10 : $data['limit'];
        $page =  ( $data['page'] - 1 ) * $limit;
        $where[] = array('order_comment.product_id','=',$data['product_id']);
        $where[] = array('order_comment.comment_status','=',1);
        $effects=array();
        $info = $this->join('users','users.user_id','=','order_comment.user_id')->where($where)
                    ->select('order_comment.*','users.user_id','users.user_name','users.user_avatar')->orderBy('comment_id','desc')->paginate($limit , $page);
        $effects=$info->items();
        $results['count']=$info->count();
        $results['currentPage']=$info->currentPage();
        $results['limit']=$info->perPage();
        $results['lastPage']=$info->lastPage();
        $results['data']=$effects;
        if(!empty($effects)){
             // $this->LogInfo('獲取收貨地址列表' , $effects , 'getUserAddress' , true);//开启日志记录
            return $this->success( __('api.list_success'), $results);
        }else{
            return $this->success( __('api.list_null'), $results);
        }
    }


    /**
     * @param $data
     * @return mixed
     * 獲取我的所有评论
     */
    public function getMyComment($data){
        $limit = empty($data['limit']) ? 10 : $data['limit'];
        $page =  ( $data['page'] - 1 ) * $limit;
        $where[] = array('user_id','=',$data['user_id']);
        $where[] = array('order_comment.comment_status','=',1);
        $effects=array();
        $info = $this->where($where)->select('*')->orderBy('comment_id','desc')->paginate($limit , $page);
        $effects=$info->items();
        $results['count']=$info->count();
        $results['currentPage']=$info->currentPage();
        $results['limit']=$info->perPage();
        $results['lastPage']=$info->lastPage();
        $results['data']=$effects;
        if(!empty($effects)){
             // $this->LogInfo('獲取收貨地址列表' , $effects , 'getUserAddress' , true);//开启日志记录
            return $this->success( __('api.list_success'), $results);
        }else{
            return $this->success( __('api.list_null'), $results);
        }
    }

/**************************************************common(api和admin共用)***********************************************************/
}
