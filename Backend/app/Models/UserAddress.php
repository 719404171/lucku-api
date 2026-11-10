<?php
/*
 * File: UserAddress.php
 * Project: NewDemo
 * Created Time: 2024-06-25 09:52:06 am
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-07-15 3:30:40 pm
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
 * App\Models\UserAddress
 *
 */

class UserAddress extends Base{

    protected $table = 'user_address';
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
	   static::addGlobalScope('user_address', function (Builder $builder) {
		  return $builder->where('user_address.address_status', '<>', -1);
	   });
    }

/*********************************************************admin**********************************************************************/


/**********************************************************api**********************************************************************/

    /**
     * @param $token
     * @return mixed
     * 獲取收貨地址列表
     */
    public function getUserAddress($data){
        $limit = empty($data['limit']) ? 10 : $data['limit'];
        $page =  ( $data['page'] - 1 ) * $limit;
        $where[] = array('user_id','=',$data['user_id']);
        $where[] = array('address_status','=',1);
        if($data['type']==1){
            $where[] = array('address_default','=',1);
        }elseif ($data['type']==2) {
            $where[] = array('address_default','=',0);
        }
        $effects=array();
        $info = $this ->where($where)->select('*')->orderBy('address_default','desc')->paginate($limit , $page);
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
     * @param $token
     * @param $data
     * @return mixed
     * 添加收貨地址
     */
    public function addUserAddress($data){
        DB::beginTransaction();
        try {
            if(!empty($data['address_default'])){
                $this->where('user_id',$data['user_id'])->update(['address_default'=> 0, 'updated_at'=>date('Y-m-d H:i:s')]);
            }
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
            $effects= $this->insert($data);
            if(!empty($effects)){
                // $this->LogInfo('添加收貨地址' , $effects , 'addUserAddress' , true);//开启日志记录
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
     * @param $token
     * @param $data
     * @return mixed
     * 編輯收貨地址
     */
    public function editUserAddress($data){
        DB::beginTransaction();
        try {
            $data['updated_at'] = date('Y-m-d H:i:s');
            if(!empty($data['address_default'])){
                $this->where('user_id',$data['user_id'])->update(['address_default'=> 0, 'updated_at'=>$data['updated_at']]);
            }
            $effects= $this->where('address_id',$data['address_id'])->where('user_id',$data['user_id'])->update($data);
            if(!empty($effects)){
                // $this->LogInfo('添加收貨地址' , $effects , 'editUserAddress' , true);//开启日志记录
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
     * 刪除收貨地址
     */
    public function delUserAddress($data){
        DB::beginTransaction();
        try {
            if(!empty($data['address_id'])){
                foreach ($data['address_id'] as $key => $value) {
                    $effects = $this->where('address_id',$value)->where('user_id',$data['user_id'])->delete();
                }
            }
            if(!empty($effects)){
                 // $this->LogInfo('刪除收貨地址' , $effects , 'delUserAddress' , true);//开启日志记录
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
}
