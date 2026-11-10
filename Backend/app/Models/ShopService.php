<?php
/*
 * File: ShopService.php
 * Project: NewDemo
 * Created Time: 2024-07-02 04:19:53 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-07-15 3:30:12 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2024 成都睿創科技有限公司
 */



namespace App\Models;

use DateTimeInterface;
use App\Models\Base;
use App\Models\WeekWorkday;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * App\Models\ShopService
 *
 * @mixin \Eloquent
 */
class ShopService extends base{

    use Notifiable;
    public $timestamps = true;
    protected $table = 'shop_service';

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
	   static::addGlobalScope('shop_service', function (Builder $builder) {
		  return $builder->where('shop_service.service_status', '<>', -1);
	   });
    }
/*********************************************************admin**********************************************************************/

    /**
     * 獲取客服列表
     * @param $data
     * @return array
     */
    public function getShopServiceListWeb($data){
        $limit = empty($data['limit']) ? 10 : $data['limit'];
        $page =  ( $data['page'] - 1 ) * $limit;
        $where[] = array('service_status','<>',-1);
        $datas['data'] = $this->where($where)
            ->offset($page)->limit($limit)
            ->orderBy('service_id','desc')
            ->get()->toArray();
        $datas['count'] = $this ->where($where)->count();
        return $this->success(__('web.success'),$datas);
    }

    /**
     * 添加/編輯客服信息
     * @param $data
     * @return array
     */
    public function handleShopServiceWeb($data){
        if(empty($data['service_id'])){
            $data['created_at'] = self::getDate();
        }
        $datas = $this->updateOrInsert(['service_id'=>$data['service_id']], $data);
        if($datas){
            return $this->success(__('web.set_success'));
        }else{
            return $this->error(__('web.set_fail'));
        }
    }

    /**
     * 刪除客服信息
     * @param $serviceID
     * @return array
     */
    public function deleteShopServiceWeb($serviceID){
        $data = self::softDelete('shop_service','service_id',$serviceID,'service_status',-1);
        if($data){
            return $this->success(__('web.delete_success'));
        }else{
            return $this->error(__('web.delete_fail'));
        }
    }










/**********************************************************api**********************************************************************/
    /**
     * @param $data
     * @return mixed
     * 獲取客服
     */
    public function getShopServiceApi(){
        $where[] = array('service_status','=',1);
        $results = $this->where($where)->orderBy('service_id','desc')->first();
        if(!empty($results)){
            return $this->success( __('api.list_success'), $results);
        }else{
            return $this->success( __('api.list_null'), $results);
        }
    }






/**************************************************common(api和admin共用)***********************************************************/



}
