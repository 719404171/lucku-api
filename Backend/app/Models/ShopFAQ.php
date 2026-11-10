<?php
/*
 * File: ShopFAQ.php
 * Project: NewDemo
 * Created Time: 2024-07-02 09:17:20 am
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-07-23 4:01:07 pm
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
 * App\Models\ShopFAQ
 *
 */
class ShopFAQ extends Base{

    use Notifiable;
    public $timestamps = true;
    protected $table = 'shop_faq';

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
        $this->AppDetail = ['faq_id',$this->language.'faq_title as faq_title',$this->language.'faq_result as faq_result','faq_status','created_at'];
    }

/*********************************************************全局作用域注册**********************************************************************/
    protected static function boot(){
       parent::boot();
	   static::addGlobalScope('shop_faq', function (Builder $builder) {
		  return $builder->where('shop_faq.faq_status', '<>', -1);
	   });
    }

/*********************************************************admin**********************************************************************/
    /**
     * 獲取常見問題列表
     * @param $data
     * @param $language
     * @return array
     */
    public function getFaqListWeb($data,$language){
        $limit = empty($data['limit']) ? 10 : $data['limit'];
        $page =  ( $data['page'] - 1 ) * $limit;
        $where[] = array('faq_status','<>',-1);
        if(!empty($data['faq_title'])){
            $where[] = array($language.'faq_title','like','%'.$data['faq_title'].'%');
        }
        if(isset($data['faq_status']) && (!empty($data['faq_status']) || is_numeric($data['faq_status']))){
            $where['faq_status'] = $data['faq_status'];
        }
        $datas['data'] = $this->where($where)
            ->offset($page)->limit($limit)
            ->orderBy('faq_id','desc')
            ->get()->toArray();
        $datas['count'] = $this ->where($where)->count();
        return $this->success(__('web.success'),$datas);
    }

    /**
     * 添加/編輯常見問題
     * @param $data
     * @return array
     */
    public function handleFaqWeb($data){
        if(empty($data['faq_id'])){
            $data['created_at'] = self::getDate();
        }
        $datas = $this->updateOrInsert(['faq_id'=>$data['faq_id']], $data);
        if($datas){
            return $this->success(__('web.set_success'));
        }else{
            return $this->error(__('web.set_fail'));
        }
    }

    /**
     * 刪除常見問題
     * @param $FAQID
     * @return array
     */
    public function deleteFaqWeb($FAQID){
        $data = self::softDelete('shop_faq','faq_id',$FAQID,'faq_status',-1);
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
     * 獲取常見問題
     */
    public function getShopFAQApi(){
        $where[] = array('faq_status','=',1);
        $results = $this->where($where)->orderBy('faq_id','desc')->get()->toArray();
        if(!empty($results)){
            return $this->success( __('api.list_success'), $results);
        }else{
            return $this->success( __('api.list_null'), $results);
        }
    }



/**************************************************common(api和admin共用)***********************************************************/









/**************************************************END***********************************************************/

}
