<?php
/*
 * File: Privacy.php
 * Created Time: 2024-05-30 3:37:58 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-07-15 3:28:18 pm
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
 * App\Models\Privacy
 */
class Privacy extends Base{

    use Notifiable;
    protected $table = 'privacy';
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

	   static::addGlobalScope('privacy', function (Builder $builder) {
		  return $builder->where('privacy.privacy_status',  '<>', -1);
	   });
    }


/*********************************************************admin**********************************************************************/
    /**
     * 修改隱私協議
     * @param $data
     * @return array
     */
    public function editPrivacyWeb($data){
        if(empty($data['privacy_id'])){
            $data['created_at'] = self::getDate();
        }
        $datas = $this->updateOrInsert(['privacy_id'=>$data['privacy_id']], $data);
        if($datas){
            return $this->success(__('web.set_success'));
        }else{
            return $this->error(__('web.set_fail'));
        }
    }


/**********************************************************api**********************************************************************/



    /**
     * @param $data
     * @return array
     * 獲取用戶協議
     */
    public function getprivacyApi($data){
        $limit = empty($data['limit']) ? 10 : $data['limit'];
        $page = empty($data['page']) ? 1 : $data['page'];
        $where[] = array('privacy_status','=',1);
        $effects=array();
        $info = $this ->where($where)->select('*')->orderBy('privacy_id','desc')->orderBy('created_at','desc')->paginate($limit , $page);
        $effects=$info->items();
        $results['count']=$info->count();
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





/**************************************************common(api和admin共用)***********************************************************/

}
