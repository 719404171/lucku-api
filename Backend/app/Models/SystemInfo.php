<?php
/*
 * File: SystemInfo.php
 * Created Time: 2024-05-29 11:27:52 am
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-07-18 5:54:27 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2024 成都睿創科技有限公司
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
 * App\Models\SystemInfo
 *
 */
class SystemInfo extends Base{

    use Notifiable;
    public $timestamps = true;
    protected $table = 'system_info';

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
        $this->AppDetail = ['system_id','user_id','system_type',$this->language.'system_content as system_content','is_read','created_at','updated_at'];
    }

/*********************************************************全局作用域注册**********************************************************************/
    protected static function boot(){
       parent::boot();
	   static::addGlobalScope('system_info', function (Builder $builder) {
		  return $builder->where('system_info.system_status', '<>', -1);
	   });
    }

/*********************************************************admin**********************************************************************/


/**********************************************************api**********************************************************************/

    /**
     * @param $data
     * @return array
     * 獲取系統消息
     */
    public function getSystemInfo($data){
        $limit = empty($data['limit']) ? 10 : $data['limit'];
        $page =  ( $data['page'] - 1 ) * $limit;
        // 中獎資訊（system_type=4）不限制用戶，顯示所有用戶的中獎記錄
        if (empty($data['system_type']) || $data['system_type'] != 4) {
            $where[] = array('user_id','=',$data['user_id']);
        }
        $where[] = array('system_status','=',1);
        if(!empty($data['system_type']) && $data['system_type']!=5){
            $where[] = array('system_type','=',$data['system_type']);
        }
        if($data['is_read']!=3){
            $where[] = array('is_read','=',$data['is_read']);
        }
        $effects=array();
        $info = $this ->where($where)->select($this->AppDetail)->orderBy('is_read','desc')->orderBy('created_at','desc')->paginate($limit , $page);
        $effects=$info->items();
        foreach ($effects as $key => $value) {
            $effects[$key]['system_img']=config('services.basics.base_logo');
        }
        $results['count']=$info->total();
        $results['currentPage']=$info->currentPage();
        $results['limit']=$info->perPage();
        $results['lastPage']=$info->lastPage();
        $results['data']=$effects;
        if(!empty($effects)){
            return $this->success( __('api.list_success'), $results);
        }else{
            return $this->success( __('api.list_null'), $results);
        }
    }



    /**
     * @param $data
     * @return array
     * 編輯系統消息
     */
    public function editSystemInfo($data){
        DB::beginTransaction();
        try {
            $info['is_read'] = 2;
            $info['updated_at'] = date('Y-m-d H:i:s');
            $system_id=$data['system_id'];
            if(!empty($system_id)){
                foreach ($system_id as $key => $value) {
                    $effects = $this->where('system_id',$system_id[$key])->where('user_id',$data['user_id'])->update($info);
                }
            }
            if(!empty($effects)){
                // $this->LogInfo('多语言日志' , $results , 'lang' , true);//开启日志记录
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
     * @return array
     * 刪除系統消息
     */
    public function DelSystemInfo($data){
        DB::beginTransaction();
        try {
            if(!empty($data['system_id'])){
                foreach ($data['system_id'] as $key => $value) {
                    $effects = $this->where('system_id',$value)->where('user_id',$data['user_id'])->delete();
                }
            }
            if(!empty($effects)){
                // $this->LogInfo('多语言日志' , $results , 'lang' , true);//开启日志记录
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
     * @param $data
     * @return array
     * 添加系統消息
     */
    public function addSystemInfo($user_id,$system_type,$system_content,$en_system_content='',$order_id=null){
        DB::beginTransaction();
        try {
            $info['user_id'] = $user_id;
            $info['system_type'] = $system_type;//消息類型  1,服務 2,產品  3,系統 4,其他
            $info['system_content'] = $system_content;
            $info['en_system_content'] = $en_system_content;
            $info['order_id'] = $order_id ?? '';
            $info['is_read'] = 1;
            $info['created_at'] = date('Y-m-d H:i:s');
            $info['updated_at'] = date('Y-m-d H:i:s');
            $effects = $this->insert($info);
            if(!empty($effects)){
                // $this->LogInfo('多语言日志' , $results , 'lang' , true);//开启日志记录
                DB::commit();
                return $this->success( __('api.add_success'));
            }else{
                DB::rollBack();
                return $this->error( __('api.add_fail'));
            }
        }catch (\Exception $e) {
            DB::rollBack();
            return $this->error(__('api.add_fail'),$e->getMessage());
        }
    }





/**************************************************END***********************************************************/

}
