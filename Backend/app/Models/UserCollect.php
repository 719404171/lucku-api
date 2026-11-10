<?php
/*
 * File: UserCollect.php
 * Project: NewDemo
 * Created Time: 2024-07-02 03:31:49 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2026-06-04 3:50:56 pm
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
use Illuminate\Foundation\Auth\User as Authenticatable;


/**
 * App\Models\UserCollect
 *
 */
class UserCollect extends Base{

    
    use Notifiable;
    protected $table = 'user_collect';
    protected $primaryKey = 'collect_id';
    public $timestamps = true;

    /**
     * 为数组 / JSON 序列化准备日期。
     */
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format($this->dateFormat ?: 'Y-m-d H:i:s');
    }


    /**
     * 函數構造
     */
    public function __construct(){
        $this->language = app('global_language') == "en" ? "en_" : "";
    }

/*********************************************************关联**********************************************************************/ 


/*********************************************************admin**********************************************************************/


/**********************************************************api**********************************************************************/
    /**
     * @param $data
     * @return mixed
     * 收藏/取消收藏商品
     */
    public function CollectProduct($data){
        DB::beginTransaction();
        try {
            $collect_effects=null;
            $Uncollect_effects=null;
            $where[] = array('users.user_id','=',$data['user_id']);
            $where[] = array('collect.product_id','=',$data['product_id']);
            $datas = $this->from('users as users')->join('user_collect as collect','collect.user_id','=','users.user_id')->where($where)->first();
            if(empty($datas)){
                $info = [
                    'user_id' => $data['user_id'],
                    'product_id' => $data['product_id'],
                    'collect_type' => $data['product_type'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
                $collect_effects= $this->insert($info);
            }else{
                $Uncollect_effects = $this->where('collect_id',$datas['collect_id'])->delete();
            }
            //收藏成功
            if(!empty($collect_effects)){
                DB::commit();
                return $this->success( __('api.collect_success'));
            }
            //取消收藏成功
            if(!empty($Uncollect_effects)){
                DB::commit();
                return $this->success( __('api.cancel_collect_success'));
            }
        }catch (\Exception $e) {
            DB::rollBack();
            return $this->error( __('api.collect_fail') , $e->getMessage());
        }
    }




    /**
     * 獲取用戶收藏列表
     * @param $data
     * @param $user
     * @return mixed
     */
    public function getUsersCollect($data){
        $results = User::with(['getUserCollect'=>function($query) use($data){
            $limit = empty($data['limit']) ? 10 : $data['limit'];
            $page =  ( $data['page'] - 1 ) * $limit;
            $query->join('product as p','p.product_id','=','user_collect.product_id')->offset($page)->limit($limit)->where('p.product_status',1)->orderBy('user_collect.created_at','desc')->select('*');
        }])->where('user_id',$data['user_id'])
           ->select('user_id','user_name','user_phone','user_avatar','user_balance')
           ->first();
         $results['get_user_collect_num']=empty(count($results['getUserCollect'])) ? 0 : count($results['getUserCollect']);
         if(!empty($results['getUserCollect'])){
            foreach ($results['getUserCollect'] as $key => $value) {
                // 產品詳細信息
                $parameters['user_id']=$data['user_id'];
                $parameters['product_id']=$results['getUserCollect'][$key]['product_id'];
                $Product=new Product;
                $results['getUserCollect'][$key]['ProductIofo'] = $Product->CommonProductSpecs($parameters);  
                
            }
        }
        if(!empty($results)){
             // $this->LogInfo('獲取收貨地址列表' , $effects , 'getUserAddress' , true);//开启日志记录
            return $this->success( __('api.list_success'), $results);
        }else{
            return $this->success( __('api.list_null'), $results);
        }
    }

/**************************************************common(api和admin共用)***********************************************************/


}
