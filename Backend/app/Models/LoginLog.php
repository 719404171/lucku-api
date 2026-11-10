<?php
/*
 * File: LoginLog.php
 * Created Time: 2024-05-29 11:27:52 am
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-07-15 3:26:08 pm
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
 * App\Models\LoginLog
 *
 */
class LoginLog extends Base
{
    protected $table = 'login_log';
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

	   static::addGlobalScope('getui', function (Builder $builder) {
		  return $builder->where('getui.getui_status', '<>', -1);
	   });
    }


/*********************************************************admin**********************************************************************/
   public function getLoginLog(){
    //   $datas= $this->latest('login_id')->get()->toArray();
     $datas= $this->oldest('login_id')->get()->toArray();
      if(!empty($datas)){
         foreach ($datas as $key => $value) {
          if($datas[$key]['login_nation']!=''){
            if($datas[$key]['login_region']==''){
                $datas[$key]['login_address']=$datas[$key]['login_nation'];
            }else{
                $datas[$key]['login_address']=$datas[$key]['login_nation'].'-'.$datas[$key]['login_region'].$datas[$key]['login_city'];
            }
          }else{
            $datas[$key]['login_address']='未知';
          }
         }
      }
    return $datas;
}

/**********************************************************api**********************************************************************/

/**************************************************common(api和admin共用)***********************************************************/
}
