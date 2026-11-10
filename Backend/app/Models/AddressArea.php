<?php
/*
 * File: AddressArea.php
 * Project: NewDemo
 * Created Time: 2024-07-02 09:17:20 am
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-07-26 10:53:25 am
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
 * App\Models\AddressArea
 *
 */
class AddressArea extends Base{

    use Notifiable;
    public $timestamps = true;
    protected $table = 'address_area';

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
        $this->AppDetail = ['area_id',$this->language.'area_name as area_name','area_parent','area_status','created_at'];
    }

/*********************************************************全局作用域注册**********************************************************************/
    protected static function boot(){
       parent::boot();
	   static::addGlobalScope('address_area', function (Builder $builder) {
		  return $builder->where('address_area.area_status', '<>', -1);
	   });
    }

/*********************************************************admin**********************************************************************/
    /**
     * 獲取区域选择列表
     * @param $data
     * @param $language
     * @return array
     */
    public function getAreaListWeb($data,$language){
        $limit = empty($data['limit']) ? 10 : $data['limit'];
        $page =  ( $data['page'] - 1 ) * $limit;
        $where[] = array('area_status','<>',-1);
        if(!empty($data['area_title'])){
            $where[] = array($language.'area_title','like','%'.$data['area_title'].'%');
        }
        if(isset($data['area_status']) && (!empty($data['area_status']) || is_numeric($data['area_status']))){
            $where['area_status'] = $data['area_status'];
        }
        $datas['data'] = $this->where($where)
            ->offset($page)->limit($limit)
            ->orderBy('area_id','desc')
            ->get()->toArray();
        $datas['count'] = $this ->where($where)->count();
        return $this->success(__('web.success'),$datas);
    }

    /**
     * 添加/編輯区域选择
     * @param $data
     * @return array
     */
    public function handleAreaWeb($data){
        if(empty($data['area_id'])){
            $data['created_at'] = self::getDate();
        }
        $datas = $this->updateOrInsert(['area_id'=>$data['area_id']], $data);
        if($datas){
            return $this->success(__('web.set_success'));
        }else{
            return $this->error(__('web.set_fail'));
        }
    }

    /**
     * 刪除区域选择
     * @param $AreaID
     * @return array
     */
    public function deleteAreaWeb($AreaID){
        $data = self::softDelete('address_area','area_id',$AreaID,'area_status',-1);
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
     * 獲取区域选择列表
     */
    public function getAddressAreaApi(){
        $where[] = array('area_status','=',1);
        $results=$this->where($where)->where('area_parent', 0)->select($this->AppDetail)->orderBy('area_id','asc')->get()->toArray();
        foreach ($results as $key => $value) {
            $results[$key]['children']=$this->where($where)->where('area_parent', '=', $results[$key]['area_id'])->select($this->AppDetail)->orderBy('area_id','asc')->get()->toArray();
        }
        if(!empty($results)){
            return $this->success( __('api.list_success'), $results);
        }else{
            return $this->success( __('api.list_null'), $results);
        }
    }



/**************************************************common(api和admin共用)***********************************************************/









/**************************************************END***********************************************************/

}
