<?php
/*
 * File: ReservationProject.php
 * Project: NewDemo
 * Created Time: 2024-07-05 03:54:55 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-07-16 6:00:09 pm
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
/**
 * App\Models\ReservationProject
 *
 */
class ReservationProject extends Base
{
    use Notifiable;
    protected $table = 'reservation_project';
    protected $primaryKey = 'reservation_project_id';
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
        $this->AppDetail = ['reservation_project_id',$this->language.'reservation_project_name as reservation_project_name',
        $this->language.'reservation_project_describe as reservation_project_describe','reservation_project_stock','reservation_project_url',
        'reservation_project_badge','reservation_project_time','reservation_project_status','created_at'];
    }

/*********************************************************全局作用域注册**********************************************************************/
    protected static function boot(){
       parent::boot();
	   static::addGlobalScope('reservation_project', function (Builder $builder) {
		  return $builder->where('reservation_project.reservation_project_status', '<>', -1);
	   });
    }

/*********************************************************admin**********************************************************************/

    /**
     * 獲取預約項目列表
     * @param $data
     * @param $language
     * @return array
     */
    public function getReservationProjectWeb($data,$language){
        $limit = $data['limit'];
        $page = ($data['page'] - 1) * $limit;
        $where[] = array('reservation_project_status','<>',-1);
        if(!empty($data['reservation_project_name'])){
            $where[] = array($language.'reservation_project_name','like','%'.$data['reservation_project_name'].'%');
        }
        $file['data'] = $this->where($where)
            ->offset($page)->limit($limit)
            ->orderBy('reservation_project_id','desc')
            ->get()->toArray();
        $file['count'] = $this->where($where)->count();
        return $this->success(__('web.success'),$file);
    }

    /**
     * 添加/編輯預約項目
     * @param $data
     * @return array
     */
    public function handleReservationProjectWeb($data){
        if(empty($data['reservation_project_id'])){
            $data['created_at'] = self::getDate();
        }
        $datas = $this->updateOrInsert(['reservation_project_id'=>$data['reservation_project_id']], $data);
        if($datas){
            return $this->success(__('web.set_success'));
        }else{
            return $this->error(__('web.set_fail'));
        }
    }

    /**
     * 刪除預約項目
     * @param $ProjectID
     * @return array
     */
    public function deleteReservationProjectWeb($ProjectID){
        $data = self::softDelete('reservation_project','reservation_project_id',$ProjectID,'reservation_project_status',-1);
        if($data){
            return $this->success(__('web.delete_success'));
        }else{
            return $this->error(__('web.delete_fail'));
        }
    }


/**********************************************************api**********************************************************************/



    /**
     * @param $token
     * @return mixed
     * 獲取預約項目列表
     */
    public function getReservationProject($data){
        $limit = empty($data['limit']) ? 10 : $data['limit'];
        $page =  ( $data['page'] - 1 ) * $limit;
        $where[] = array('reservation_project_status','=',1);
        $effects=array();
        $info = $this ->where($where)->select($this->AppDetail)->orderBy('reservation_project_id','desc')->paginate($limit , $page);
        $effects=$info->items();
        foreach ($effects as $key => $value) {
            $reservation_project_id=$effects[$key]['reservation_project_id'];
            $RealCount= UserReservation::where('reservation_project_id',$reservation_project_id)->where('user_date',date('Y-m-d'))->where('user_status','<',3)->count();
            $effects[$key]['TotalCount']= $RealCount;
            $MyReservation= UserReservation::where('reservation_project_id',$reservation_project_id)->where('user_id',$data['user_id'])
                                                                         ->where('user_date',date('Y-m-d'))->where('user_status','<',3)->get()->toArray();
            foreach ($MyReservation as $k => $v) {
                //计算等待时间
                 $count= UserReservation::where('reservation_project_id',$reservation_project_id)->where('user_numerical','<',$MyReservation[$k]['user_numerical'])
                 ->where('user_date',date('Y-m-d'))->where('user_status','<',3)->count();
                 $MyReservation[$k]['scheduled_time'] = $count * $effects[$key]['reservation_project_time'];   
                // //获取二维码
                if(empty($MyReservation[$k]['reservation_code'])){
                    $effects[$key]['reservation_code']=$this->getQRCode($MyReservation[$k]['user_ref']); 
                }
            }
            $effects[$key]['MyReservation'] =$MyReservation;                                              
            $effects[$key]['IsHas']= empty($effects[$key]['MyReservation']) ? 0 : 1;                                   
        }
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








/**************************************************common(api和admin共用)***********************************************************/



}
