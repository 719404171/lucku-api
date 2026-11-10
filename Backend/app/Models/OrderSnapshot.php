<?php
/*
 * File: OrderSnapshot.php
 * Project: NewDemo
 * Created Time: 2024-07-08 03:58:20 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2026-06-18 4:07:28 pm
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
 * App\Models\OrderSnapshot
 *
 */

class OrderSnapshot extends Base{

    protected $table = 'order_snapshot';
    use Notifiable;
    public $timestamps = true;
    public static $modify = false;
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


    /**
     * 修改器（修改訂單詳情用户快照）
     * @param $data
     * @return string
     */
    public function getUserSnapshotAttribute($data){
        $UserSnapshot=array();
        $UserSnapshot = json_decode($data,true);
        $UserSnapshot=$UserSnapshot['user_name'];
        return self::$modify ? $UserSnapshot: $data;
    }

    /**
     * 修改器（修改訂單詳情优惠券快照）
     * @param $data
     * @return string
     */
    public function getCouponSnapshotAttribute($data){
        $CouponSnapshot=array();
        $this->language = app('global_language') == "en" ? "en_" : "";
        $CouponSnapshot = json_decode($data,true);
        if(!empty($CouponSnapshot)){
            $CouponSnapshot=$CouponSnapshot[$this->language .'coupon_name'];
        }
        return self::$modify ? $CouponSnapshot: $data;
    }

    /**
     * 修改器（修改訂單詳情产品快照）
     * @param $data
     * @return string
     */
    public function getProductSnapshotAttribute($data){
        $ProductSnapshot=array();
        $this->language = app('global_language') == "en" ? "en_" : "";
        $ProductSnapshot = json_decode($data,true);
        $key = $this->language . 'product_name';
        $ProductSnapshot = $ProductSnapshot[$key] ?? ($ProductSnapshot['product_name'] ?? '');
        return self::$modify ? $ProductSnapshot : $data;
    }

    
/*********************************************************全局作用域注册**********************************************************************/
    protected static function boot(){
       parent::boot();
	   static::addGlobalScope('order_snapshot', function (Builder $builder) {
		  return $builder->where('order_snapshot.snapshot_status', '<>', -1);
	   });
    }

/*********************************************************admin**********************************************************************/


/**********************************************************api**********************************************************************/




/**************************************************common(api和admin共用)***********************************************************/


    /**
     * @param $token
     * @return mixed
     * 添加訂單快照
     */
    public function addOrderSnapshot($data){
        DB::beginTransaction();
        try {
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
            $effects= $this->insert($data);
            if(!empty($effects)){
                // $this->LogInfo('添加收貨地址' , $effects , 'addUserAddress' , true);//开启日志记录
                DB::commit();   return $effects;
            }else{
                DB::rollBack();   return false;
            }
        }catch (\Exception $e) {
            DB::rollBack();   return  $e->getMessage();
        }
    }











/**************************************************end***********************************************************/





    

}
