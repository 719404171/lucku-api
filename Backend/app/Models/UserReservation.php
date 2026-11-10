<?php
/*
 * File: UserReservation.php
 * Project: NewDemo
 * Created Time: 2024-07-05 03:54:55 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-07-17 9:32:17 am
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
 * App\Models\UserReservation
 *
 */
class UserReservation extends Base
{
    use Notifiable;
    protected $table = 'user_reservation';
    protected $primaryKey = 'user_reservation_id';
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
	   static::addGlobalScope('user_reservation', function (Builder $builder) {
		  return $builder->where('user_reservation.user_reservation_status', '<>', -1);
	   });
    }

/*********************************************************admin**********************************************************************/
    /**
     * 獲取用戶排號列表
     * @param $data
     * @param $language
     * @return array
     */
    public function getUserReservationWeb($data,$language){
        $limit = $data['limit'];
        $page = ($data['page'] - 1) * $limit;
        $where[] = array('user_reservation.user_reservation_status','<>',-1);
        if(!empty($data['project_name'])){
            $where[] = array('reservation_project.'.$language.'project_name','like','%'.$data['project_name'].'%');
        }
        if(!empty($data['user_name'])){
            $where[] = array('users.user_name','like','%'.$data['user_name'].'%');
        }
        $file['data'] = $this->join('users','user_reservation.user_id','=','users.user_id')
            ->join('reservation_project','reservation_project.reservation_project_id','=','user_reservation.reservation_project_id')
            ->where($where)
            ->select('user_reservation.*','reservation_project.'.$language.'reservation_project_name as reservation_project_name','users.user_name')
            ->offset($page)->limit($limit)
            ->orderBy('user_reservation_id','desc')
            ->get()->toArray();
        $file['count'] = $this->join('users','user_reservation.user_id','=','users.user_id')
            ->join('reservation_project','reservation_project.reservation_project_id','=','user_reservation.reservation_project_id')
            ->where($where)
            ->count();
        return $this->success(__('web.success'),$file);
    }

    /**
     * 修改用戶排號狀態
     * @param $data
     * @return array
     */
    public function editUserReservationWeb($data){
        $datas = $this->where('user_reservation_id',$data['user_reservation_id'])
            ->update(['user_status' => $data['user_status']]);
        if($datas){
            return $this->success(__('web.edit_success'));
        }else{
            return $this->error(__('web.edit_fail'));
        }
    }

    /**
     * 刪除用戶排號記錄
     * @param $user_reservation_id
     * @return array
     */
    public function deleteUserReservationWeb($user_reservation_id){
        $data = self::softDelete('user_reservation','user_reservation_id',$user_reservation_id,'user_reservation_status',-1);
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
     * 獲取用戶項目票據列表
     */
    public function getUserReservation($data){
        $limit = empty($data['limit']) ? 10 : $data['limit'];
        $page =  ( $data['page'] - 1 ) * $limit;
        $where[] = array('user_id','=',$data['user_id']);
        if(!empty($data['date'])){
            $where[] = array('user_date','=',$data['date']);
        }
        $effects=array();
        $info = $this->join('reservation_project','reservation_project.reservation_project_id','=','user_reservation.reservation_project_id')
                     ->where($where)
                     ->select( 'user_reservation.*','reservation_project_url','reservation_project.'.$this->language.'reservation_project_name as reservation_project_name'
                              ,'reservation_project.'.$this->language.'reservation_project_describe as reservation_project_describe','reservation_project_stock','reservation_project_time')
                     ->orderBy('user_reservation.user_numerical','asc')->paginate($limit , $page);
        $effects=$info->items();
        foreach ($effects as $key => $value) {
            //计算等待时间
            $count= $this::where('reservation_project_id',$effects[$key]['reservation_project_id'])
                                    ->where('user_numerical','<',$effects[$key]['user_numerical'])->where('user_date',date('Y-m-d'))->where('user_status','<',3)->count();
            $effects[$key]['scheduled_time'] = $count * $effects[$key]['reservation_project_time'];
            // //获取二维码
            if(empty($effects[$key]['reservation_code'])){
                $effects[$key]['reservation_code']=$this->getQRCode($effects[$key]['user_ref']);
            }
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



    /**
     * @param $token
     * @param $data
     * @return mixed
     * 取票
     */
    public function addUserReservation($data){
        $ProjectInfo=ReservationProject::where('reservation_project_id',$data['reservation_project_id'])->first();
        //今日所有票據
        $TotalCount= UserReservation::where('reservation_project_id',$data['reservation_project_id'])->where('user_date',date('Y-m-d'))->count();
        //今日真實可用票據
        $RealCount= UserReservation::where('reservation_project_id',$data['reservation_project_id'])->where('user_date',date('Y-m-d'))->where('user_status','<',3)->count();
        if($ProjectInfo['reservation_project_stock'] <= $RealCount){
            return $this->error( __('api.reservation_null'));
        }
        $string='';
        if($TotalCount<10){ $string='_0';}elseif($TotalCount>=10){ $string='_0'; }else{ $string='_'; }
        DB::beginTransaction();
        try {
            $data['user_numerical'] = $TotalCount+1;
            $data['user_ref'] = $ProjectInfo['reservation_project_badge'].$string.$data['user_numerical'];
            $data['reservation_code'] = $this->getQRCode($data['user_ref']);
            $data['user_status'] = 1;
            $data['user_date'] = date('Y-m-d');
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
     * 簽到
     */
    public function CheckReservation($data){
        DB::beginTransaction();
        try {
            $data['user_status'] = 2;
            $data['updated_at'] = date('Y-m-d H:i:s');
            $effects= $this->where('user_reservation_id',$data['user_reservation_id'])->update($data);
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
     * @param $token
     * @param $data
     * @return mixed
     * 取消排队
     */
    public function CancelReservation($data){
        DB::beginTransaction();
        try {
            $data['user_status'] = 3;
            $data['updated_at'] = date('Y-m-d H:i:s');
            $effects= $this->where('user_reservation_id',$data['user_reservation_id'])->where('user_id',$data['user_id'])->update($data);
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











/**************************************************common(api和admin共用)***********************************************************/



}
