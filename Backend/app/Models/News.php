<?php
/*
 * File: News.php
 * Project: NewDemo
 * Created Time: 2024-06-24 03:58:15 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-07-15 3:26:32 pm
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
 * App\Models\News
 */
class News extends Base{

    use Notifiable;
    public $timestamps = true;
    protected $table = 'news';

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
        $this->AppDetail = ['news_id',$this->language.'news_title as news_title','news_img',$this->language.'news_text as news_text','reading_num','news_status','created_at'];
    }

/*********************************************************全局作用域注册**********************************************************************/
    protected static function boot(){
       parent::boot();
	   static::addGlobalScope('news', function (Builder $builder) {
		  return $builder->where('news.news_status', '<>', -1);
	   });
    }

/*********************************************************admin**********************************************************************/

    /**
     * 获取咨询列表
     * @param $data
     * @param $language
     * @return array
     */
    public function getNewsListWeb($data,$language){
        $limit = $data['limit'];
        $page = ($data['page'] - 1) * $limit;
        $where[] = array('news_status','<>',-1);
        $datas['data'] = $this->where($where)
            ->where(function ($query) use ($data,$language){
                if(!empty($data['news_title'])){
                    $query->whereRaw('(concat('.$language.'news_title,'.$language.'news_text) like "%' . $data['news_title'] . '%")');
                }
            })
            ->offset($page)->limit($limit)
            ->orderBy('news_id','desc')
            ->get()->toArray();
        $datas['count'] = $this->where($where)
            ->where(function ($query) use ($data,$language){
                if(!empty($data['news_title'])){
                    $query->whereRaw('(concat('.$language.'news_title,'.$language.'news_text) like "%' . $data['news_title'] . '%")');
                }
            })->count();
        return $this->success(__('web.success'),$datas);
    }

    /**
     * 添加/編輯咨询
     * @param $data
     * @return array
     */
    public function handleNewsWeb($data){
        if(empty($data['news_id'])){
            $data['created_at'] = self::getDate();
        }
        $datas = $this->updateOrInsert(['news_id'=>$data['news_id']], $data);
        if($datas){
            return $this->success(__('web.set_success'));
        }else{
            return $this->error(__('web.set_fail'));
        }
    }

    /**
     * 刪除咨询
     * @param $newsID
     * @return array
     */
    public function deleteNewsWeb($newsID){
        $data = self::softDelete('news','news_id',$newsID,'news_status',-1);
        if($data){
            return $this->success(__('web.delete_success'));
        }else{
            return $this->error(__('web.delete_fail'));
        }
    }

/**********************************************************api**********************************************************************/

    /**
     * @param $data
     * @return array
     * 獲取最新資訊
     */
    public function getNewsApi($data){
        $limit = empty($data['limit']) ? 10 : $data['limit'];
        $page =  ( $data['page'] - 1 ) * $limit;
        $where[] = array('news_status','=',1);
        $effects=array();
        $info = $this ->where($where)->select($this->AppDetail)->orderBy('created_at','desc')->paginate($limit , $page);
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



    /**
     * @param $data
     * @return array
     * 獲取最新資訊详情
     */
    public function getNewsDetails($data){
        $where[] = array('news_id','=',$data['news_id']);
        $this ->where($where)->increment('reading_num', 1);//阅读数+1
        $info = $this ->where($where)->select($this->AppDetail)->paginate();
        $effects=$info->items();
        $results['count']=$info->count();
        $results['currentPage']=$info->currentPage();
        $results['limit']=$info->perPage();
        $results['lastPage']=$info->lastPage();
        $results['data']=$effects;
        if(!empty($effects)){
            return $this->success( __('api.info_success'), $results);
        }else{
            return $this->success( __('api.info_fail'), $results);
        }
    }

/**************************************************common(api和admin共用)***********************************************************/









/**************************************************END***********************************************************/

}
