<?php
/*
 * File: ShopStripe.php
 * Project: NewDemo
 * Created Time: 2024-06-13 02:32:26 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-12-04 3:13:44 pm
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
 * App\Models\ShopStripe
 */
class ShopStripe extends Base{


    protected $table = 'shop_stripe';
    protected $primaryKey = 'stripe_id';
    use Notifiable;
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
        // $this->language = app('global_language') == "en" ? "en_" : "";
    }

/*********************************************************全局作用域注册**********************************************************************/
    protected static function boot(){
       parent::boot();

	   static::addGlobalScope('shop_stripe', function (Builder $builder) {
		  return $builder->where('shop_stripe.stripe_status',  '<>', -1);
	   });
    }



/**********************************************************admin**********************************************************************/

    /**
     * 獲取Stripe列表
     * @param $data
     * @return array
     */
    public function getShopStripeListWeb($data){
        $limit = empty($data['limit']) ? 10 : $data['limit'];
        $page =  ( $data['page'] - 1 ) * $limit;
        $where[] = array('stripe_status','<>',-1);
        $datas['data'] = $this->where($where)
            ->offset($page)->limit($limit)
            ->orderBy('stripe_id','desc')
            ->get()->toArray();
        $datas['count'] = $this ->where($where)->count();
        return $this->success(__('web.success'),$datas);
    }

    /**
     * 添加/編輯Stripe信息
     * @param $data
     * @return array
     */
    public function handleShopStripeWeb($data){
        DB::beginTransaction();
        $stripe = true;
        if($data['stripe_enable'] == 1){
            if($this->where('stripe_status','<>',-1)->count() > 0){
                $stripe = $this->where('stripe_enable',1)->update(['stripe_enable'=>0]);
            }
        }
        if($stripe){
            if(empty($data['stripe_id'])){
                $data['created_at'] = self::getDate();
            }
            $datas = $this->updateOrInsert(['stripe_id'=>$data['stripe_id']], $data);
            if($datas){
                 $this->from('users')->where('user_status',1)->update(['stripe_id'=>NULL]);
                DB::commit();
                return $this->success(__('web.set_success'));
            }else{
                DB::rollBack();
                return $this->error(__('web.set_fail'),2);
            }
        }else{
            DB::rollBack();
            return $this->error(__('web.set_fail'),1);
        }
    }

    /**
     * 刪除Stripe信息
     * @param $StripeId
     * @return array
     */
    public function deleteShopStripeWeb($StripeId){
        $data = self::softDelete('shop_stripe','stripe_id',$StripeId,'stripe_status',-1);
        if($data){
            return $this->success(__('web.delete_success'));
        }else{
            return $this->error(__('web.delete_fail'));
        }
    }


/**********************************************************api**********************************************************************/



/**************************************************common(api和admin共用)***********************************************************/

}
