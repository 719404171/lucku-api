<?php
/*
 * File: UserCart.php
 * Project: NewDemo
 * Created Time: 2024-07-03 09:45:04 am
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-09-27 10:33:40 am
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
 * App\Models\UserCart
 *
 */

class UserCart extends Base{

    protected $table = 'user_cart';
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
    }
/*********************************************************全局作用域注册**********************************************************************/

    protected static function boot(){
       parent::boot();
	   static::addGlobalScope('user_cart', function (Builder $builder) {
		  return $builder->where('user_cart.cart_status', '<>', -1);
	   });
    }

/*********************************************************admin**********************************************************************/


/**********************************************************api**********************************************************************/

    /**
     * @param $data['user_id']
     * @return mixed
     * 獲取用戶購物車列表
     */
    public function getUserCart($data){
        $limit = empty($data['limit']) ? 10 : $data['limit'];
        $page =  ( $data['page'] - 1 ) * $limit;
        $where[] = array('u.user_id','=',$data['user_id']);
        $where[] = array('uc.cart_status','=',1);
        $where[] = array('uc.cart_num','>',0);
        $where[] = array('p.product_status','=',1);
        $effects=array();
        $info = $this->from('users as u')->join('user_cart as uc','uc.user_id','=','u.user_id')
                     ->join('product as p','uc.product_id','=','p.product_id')->where($where)->orderBy('uc.created_at','desc')
                     ->select('uc.cart_id','uc.cart_num','uc.product_relation_id','uc.created_at','p.product_id',
                              'p.product_name','p.product_img','p.product_type','p.product_price','p.product_cost',
                              'p.'.$this->language.'product_describe','p.'.$this->language.'product_synopsis')
                     ->paginate($limit , $page);
        $effects=$info->items();
        $collect=0;
        foreach ($effects as $key => $value) {
            //是否收藏
            if (!empty($data['user_id'])) {
                $coll = $this->from('users as u')->join('user_collect as c', 'c.user_id', '=', 'u.user_id')->where('u.user_status', '=', 1)
                             ->where('u.user_id', '=', $data['user_id'])->where('c.product_id', '=', $effects[$key]['product_id'])->first();
                if (empty($coll)) {
                    $effects[$key]['user_collect'] = 0;
                } else {
                    $effects[$key]['user_collect'] = 1;
                }
            } else {
                $effects[$key]['user_collect'] = 0;
            }
            //產品詳細信息
            $parameters['user_id']=$data['user_id'];
            $parameters['product_id']=$effects[$key]['product_id'];
            $Product=new Product;
            $effects[$key]['ProductInfo'] = $Product->CommonProductDetails($parameters);  
        }

        $results['count']=$info->count();
        $results['currentPage']=$info->currentPage();
        $results['limit']=$info->perPage();
        $results['lastPage']=$info->lastPage();
        $results['data']=$effects;
        if(!empty($effects)){
             // $this->LogInfo('獲取用戶購物車列表' , $effects , 'getUserCart' , true);//开启日志记录
            return $this->success( __('api.list_success'), $results);
        }else{
            return $this->success( __('api.list_null'), $results);
        }
    }




    /**
     * @param $data['user_id']
     * @param $data
     * @return mixed
     * 添加用戶購物車
     */
    public function addUserCart($data){
        DB::beginTransaction();
        try {
            $info = $this->where('user_id',$data['user_id'])->where('product_id',$data['product_id'])->where('product_relation_id',$data['product_relation_id'])->first();
            if(empty($info)){
                $data['created_at'] = date('Y-m-d H:i:s');
                $data['updated_at'] = date('Y-m-d H:i:s');
                $effects= $this->insert($data);
            }else{
                $effects= $this->where('cart_id',$info['cart_id'])->update([
                                                                            'cart_num'=>($info['cart_num'] + $data['cart_num']),
                                                                            'updated_at'=>date('Y-m-d H:i:s')
                                                                            ]);
            }
            if(!empty($effects)){
                //產品詳細信息
                $parameters['user_id']=$data['user_id'];
                $parameters['product_id']=$data['product_id'];
                $Product=new Product;
                $results = $Product->CommonProductDetails($parameters);  
                // $this->LogInfo('添加用戶購物車' , $effects , 'addUserCart' , true);//开启日志记录
                DB::commit();
                return $this->success( __('api.add_success'),$results);
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
     * @param $data['user_id']
     * @param $data
     * @return mixed
     * 編輯用戶購物車
     */
    public function editUserCart($data){
        DB::beginTransaction();
        try {
            $effects = $this->where('cart_id',$data['cart_id'])->where('user_id',$data['user_id'])->update([
                                                                         'product_relation_id'=>$data['product_relation_id'],
                                                                         'cart_num'=>$data['cart_num'],
                                                                         'updated_at'=>date('Y-m-d H:i:s')
                                                                        ]);
            if(!empty($effects)){
                $info = $this->where('cart_id',$data['cart_id'])->first();
                //產品詳細信息
                $parameters['user_id']=$info['user_id'];
                $parameters['product_id']=$info['product_id'];
                $Product=new Product;
                $results = $Product->CommonProductDetails($parameters);  
                // $this->LogInfo('添加用戶購物車' , $effects , 'editUserCart' , true);//开启日志记录
                DB::commit();
                return $this->success( __('api.edit_success'));
            }else{
                DB::rollBack();
                return $this->error( __('api.edit_fail'));
            }
        }catch (\Exception $e) {
            DB::rollBack();
            return $this->error( __('api.edit_fail') , $e->getMessage());
        }
    }

    /**
     * @param $data
     * @return mixed
     * 刪除用戶購物車
     */
    public function delUserCart($data){
        DB::beginTransaction();
        try {
            if(!empty($data['cart_id'])){
                foreach ($data['cart_id'] as $key => $value) {
                    $effects = $this->where('cart_id',$value)->where('user_id',$data['user_id'])->delete();
                }
            }
            if(!empty($effects)){
                 // $this->LogInfo('刪除用戶購物車' , $effects , 'delUserCart' , true);//开启日志记录
                DB::commit();
                return $this->success( __('api.delete_success'));
            }else{
                DB::rollBack();
                return $this->error( __('api.delete_fail'));
            }
        }catch (\Exception $e) {
            DB::rollBack();
            return $this->error( __('api.delete_fail') , $e->getMessage());
        }
    }

/**************************************************common(api和admin共用)***********************************************************/
    /**
     * @param Request $request
     * @return array
     * 購物車刪除(支付调用)
     */
    public function DeleteShoppingCart($cart_id){
        foreach ($cart_id as $key => $value) {
            $results=$this::where('cart_id',$cart_id[$key])->delete();
        }

    }



}
