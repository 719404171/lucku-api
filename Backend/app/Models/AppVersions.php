<?php
/*
 * File: AppVersions.php
 * Project: NewDemo
 * Created Time: 2024-06-13 09:59:11 am
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-07-15 3:25:20 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2024 成都睿創科技有限公司
 */


namespace App\Models;

use DateTimeInterface;
use App\Models\Base;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
/**
 * App\Models\AppVersions
 *
 */
class AppVersions extends Base{

    protected $table = 'app_versions';
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
        $this->language = app('global_language') == "en" ? "en_" : "";
    }


/*********************************************************全局作用域注册**********************************************************************/

    protected static function boot(){
       parent::boot();

	   static::addGlobalScope('versions_status', function (Builder $builder) {
		  return $builder->where('app_versions.versions_status',  '<>', -1);
	   });
    }

    
/**********************************************************admin**********************************************************************/


/**********************************************************api**********************************************************************/
    /**
     * @param $data
     * @return mixed
     * 獲取APP版本
     */
    public function getAppVersions(){
        $where[] = array('versions_status','=',1);
        $results = $this->where($where)->orderBy('versions_id','desc')->first();
        if(!empty($results)){
            return $this->success( __('api.list_success'), $results);
        }else{
            return $this->success( __('api.list_null'), $results);
        }
    }


/**************************************************common(api和admin共用)***********************************************************/

}
