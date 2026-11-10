<?php
/*
 * File: UserIntegralRecord.php
 * Project: NewDemo
 * Created Time: 2024-07-03 02:27:51 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-09-13 5:07:44 pm
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
 * App\Models\UserIntegralRecord
 */
class UserIntegralRecord extends Base{
    
    use Notifiable;
    protected $table = 'user_integral_record';
    protected $primaryKey = 'integral_id';
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
	   static::addGlobalScope('user_integral_record', function (Builder $builder) {
		  return $builder->where('user_integral_record.integral_status', '<>', -1);
	   });
    }
/*********************************************************admin**********************************************************************/


/**********************************************************api**********************************************************************/
    /**
     * 獲取用戶積分明細
     * @param $data
     * @param $user
     * @return mixed
     */
    public function getUserIntegral($data){
        $results = User::with(['getUserIntegral'=>function($query) use($data){
            if(!empty($data['integral_type'])){
                $query->where('integral_type',$data['integral_type'])->orderBy('user_integral_record.created_at','desc')->select('*');
            }else{
                $query->orderBy('user_integral_record.created_at','desc')->select('*');
            }        
        }])->where('user_id',$data['user_id'])
           ->select('user_id','user_name','user_phone','user_avatar','user_balance')
           ->first();
        $results['get_user_integral_num']=empty(count($results['getUserIntegral'])) ? 0 : count($results['getUserIntegral']);
        $limit = empty($data['limit']) ? 10 : $data['limit'];
        $page = empty($data['page']) ? 1 : $data['page'];  
        $min=($page - 1) * $limit;
        $getUserIntegralArray=array_slice( json_decode($results['getUserIntegral'] , true) , $min, $limit);
        unset($results['getUserIntegral']);
        $results['getUserIntegral']=$getUserIntegralArray;
        if(!empty($results)){
             // $this->LogInfo('獲取收貨地址列表' , $effects , 'getUserAddress' , true);//开启日志记录
            return $this->success( __('api.list_success'), $results);
        }else{
            return $this->success( __('api.list_null'), $results);
        }
    }
/**************************************************common(api和admin共用)***********************************************************/


}
