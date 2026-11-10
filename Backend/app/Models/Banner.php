<?php
/*
 * File: Banner.php
 * Project: NewDemo
 * Created Time: 2024-05-30 03:04:41 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-07-15 3:25:30 pm
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
 * App\Models\Banner
 *
 */
class Banner extends base{

    protected $table = 'banner';
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

	   static::addGlobalScope('banner_status', function (Builder $builder) {
		  return $builder->where('banner.banner_status',  '<>', -1);
	   });
    }


/*********************************************************admin**********************************************************************/

    /**
     * 獲取Banner列表
     * @param $data
     * @param $language
     * @return array
     */
    public function getBannerListWeb($data,$language){
        $limit = $data['limit'];
        $page = ($data['page'] - 1) * $limit;
        $where[] = array('banner_status','<>',-1);
        if(!empty($data['banner_title'])){
            $where[] = array($language.'banner_title','like','%'.$data['banner_title'].'%');
        }
        if(isset($data['banner_status']) && (!empty($data['banner_status']) || is_numeric($data['banner_status']))){
            $where['banner_status'] = $data['banner_status'];
        }
        $datas['data'] = $this->where($where)
            ->offset($page)->limit($limit)
            ->orderBy('banner_id','desc')
            ->get()->toArray();
        $datas['count'] = $this ->where($where)->count();
        return $this->success(__('web.success'),$datas);
    }

    /**
     * 添加/編輯Banner
     * @param $data
     * @return array
     */
    public function handleBannerListWeb($data){
        if(empty($data['banner_id'])){
            $data['created_at'] = self::getDate();
        }
        $datas = $this->updateOrInsert(['banner_id'=>$data['banner_id']], $data);
        if($datas){
            return $this->success(__('web.set_success'));
        }else{
            return $this->error(__('web.set_fail'));
        }
    }

    /**
     * 刪除Banner
     * @param $bannerID
     * @return array
     */
    public function deleteBannerListWeb($bannerID){
        $data = self::softDelete('banner','banner_id',$bannerID,'banner_status',-1);
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
     * 獲取Banner(api)
     */
    public function getBannerApi($data){
        $limit = empty($data['limit']) ? 10 : $data['limit'];
        $page =  ( $data['page'] - 1 ) * $limit;
        $where[] = array('banner_status',1);
        $effects=array();
        $info = $this ->where($where)->select('*')->orderBy('banner_id','desc')->orderBy('created_at','desc')->paginate($limit , $page);
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
