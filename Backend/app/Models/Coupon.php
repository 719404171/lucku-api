<?php
/*
 * File: Coupon.php
 * Project: NewDemo
 * Created Time: 2024-07-05 03:54:55 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-10-18 10:18:40 am
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
 * App\Models\Coupon
 */
class Coupon extends Base
{
    use Notifiable;
    protected $table = 'coupon';
    protected $primaryKey = 'coupon_id';
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
        $this->AppDetail = ['coupon_id',$this->language.'coupon_name as coupon_name','coupon_type','lowest','discount','conversion_price',
                            'expiration_time', $this->language.'instructions as instructions',];
        //每个值加前缀
        $table=$this->table;
        $this->PrefixAppDetail = array_map(function($item) use ($table) { return $table.'.' . $item ;}, $this->AppDetail);
    }
/*********************************************************全局作用域注册**********************************************************************/
    protected static function boot(){
       parent::boot();
	   static::addGlobalScope('coupon', function (Builder $builder) {
		  return $builder->where('coupon.coupon_status', '<>', -1);
	   });
    }

/*********************************************************admin**********************************************************************/

    /**
     * 獲取優惠券列表
     * @param $data
     * @param $language
     * @return mixed
     */
    public function getCouponListWeb($data,$language){
        $limit = empty($data['limit']) ? 10 : $data['limit'];
        $page =  ( $data['page'] - 1 ) * $limit;
        $where[] = array('coupon.coupon_status', '<>', -1);
        if(isset($data['coupon_type']) && (!empty($data['coupon_type']) || is_numeric($data['coupon_type']))){
            $where['coupon.coupon_type'] = $data['coupon_type'];
        }
        if(!empty($data['coupon_name'])){
            $where[] = array('coupon.'.$language.'coupon_name', 'like', '%' . $data['coupon_name'] . '%');
        }
        $datas['data'] = $this->join('admins','coupon.admin_id','=','admins.admin_id')
            ->leftjoin('user_member','coupon.lowlevel','=','user_member.member_id')
            ->where($where)
            ->select('coupon.*','admins.username','user_member.member_name',DB::raw('0 as is_end'))
            ->offset($page)->limit($limit)
            ->orderBy('coupon.coupon_id','desc')
            ->get()->toArray();
        $datas['count'] = $this->join('admins','coupon.admin_id','=','admins.admin_id')
            ->where($where)
            ->count();
        for ($i=0;$i<count($datas['data']);$i++){
            if($datas['data'][$i]['expiration_time'] < self::getDate()){
                $datas['data'][$i]['is_end'] = 1;
            }
        }
        return $datas;
    }

    /**
     * 編輯/添加優惠券列表
     * @param $data
     * @return array
     */
    public function handleCouponListWeb($data){
        $reallowlevel = UserMember::where('member_status', '=' , 1)
        ->orderBy('member_start', 'asc')
        ->select('member_id')
        ->first();
        if(empty($data['coupon_id'])){
            $data['created_at'] = self::getDate();
        }
        $data['lowest']= empty($data['lowest']) ? 0.00 : $data['lowest'];
        $data['lowlevel']= empty($data['lowlevel']) ? $reallowlevel : $data['lowlevel'];
        $res = $this->updateOrInsert(['coupon_id'=>$data['coupon_id']], $data);
        if($res){
            return $this->success(__('web.set_success'));
        }else{
            return $this->error(__('web.set_fail'));
        }
    }

    /**
     * 刪除優惠券
     * @param $couponID
     * @return array
     */
    public function deleteCouponWeb($couponID){
        $data = self::softDelete('coupon','coupon_id',$couponID,'coupon_status',-1);
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
     * 獲取優惠券列表（api）
     */
    public function getAllCoupons($data){
        $limit = empty($data['limit']) ? 10 : $data['limit'];
        $page =  ( $data['page'] - 1 ) * $limit;
        $where[] = array('coupon_status','=',1);
        $where[] = array('coupon_stock','>',0);
        $effects=array();
        $info = $this->where($where)->where(function ($query){
                    return $query->where('expiration_time','>=',date('Y-m-d H:i:s'));
                })->select($this->AppDetail)->orderBy('coupon_id','desc')->paginate($limit , $page);
        $effects=$info->items();
        //查詢是否兌換
        foreach ($effects as $key => $value) {
            if(!empty($data['user_id'])){
                $coupon_id=$effects[$key]['coupon_id'];
                $exchange = $this->from('user_exchange as ue')->where('ue.user_id',$data['user_id'])
                                ->where('ue.coupon_id',$coupon_id)->where('ue.exchange_status',1)->select('ue.exchange_id')->first();
                if(!empty($exchange)){
                    $effects[$key]['is_exchange']=1;
                }else{
                    $effects[$key]['is_exchange']=0;
                }
            }else{
                $effects[$key]['is_exchange']=0;
            }
            switch ($effects[$key]['coupon_type']) {
                case 1:
                    $effects[$key]['typeName'] = app('global_language') == "en"? 'Cash Coupon' : '現金券';
                    break;
                case 2:
                    $effects[$key]['typeName'] = app('global_language') == "en"? 'Discount Coupon' : '滿減券';
                    break;
                case 3:
                    $effects[$key]['typeName'] = app('global_language') == "en"? 'Instant Coupon' : '立折券';
                    $effects[$key]['coupon_discount'] = ($effects[$key]['coupon_discount']) * 10;
                    break;
                case 4:
                    $effects[$key]['typeName'] = app('global_language') == "en"? 'Full Coupon' : '滿折券';
                    $effects[$key]['coupon_discount'] = ($effects[$key]['coupon_discount']) * 10;
                    break;
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
     * @param $user
     * @param $id
     * @return array
     * 兌換優惠券
     */
    public function exchangeCoupon($data){
        DB::beginTransaction();
        try {
            // 鎖住用戶行：序列化同一用戶的併發兌換，避免積分被扣成負數
            $userinfo = DB::table('users')->where('user_id',$data['user_id'])->lockForUpdate()->first();
            // 鎖住優惠券行：序列化同一優惠券的併發兌換，避免庫存被扣成負數
            $coupon = DB::table('coupon')->where('coupon_id',$data['coupon_id'])->lockForUpdate()->first();
            if(empty($userinfo) || empty($coupon)){
                DB::rollBack();
                return $this->error( __('api.exchange_error'));
            }

            $lowlevelpoint = 0;
            if(!empty($coupon->lowlevel) && $coupon->lowlevel > 0) {
                $lowlevelpoint = (int)$this->from('user_member')->where('member_id',$coupon->lowlevel)->value('member_start');
            }
            if($userinfo->user_integral_all < $lowlevelpoint){
                DB::rollBack();
                return $this->error( __('api.level_not_enough'));
            }
            // 條件式扣減庫存：庫存不足時受影響行數為 0，據此回滾而不是扣成負數
            $stockAffected = DB::table('coupon')->where('coupon_id',$data['coupon_id'])
                                ->where('coupon_stock','>',0)
                                ->decrement('coupon_stock',1);
            if($stockAffected <= 0){
                DB::rollBack();
                return $this->error( __('api.exchange_stock'));
            }
            if(($userinfo->user_integral - $coupon->conversion_price) < 0){
                DB::rollBack();
                return $this->error( __('api.exchange_null'));
            }
            // 條件式扣減積分：積分不足時受影響行數為 0，據此回滾而不是扣成負數
            $integralAffected = DB::table('users')->where('user_id',$data['user_id'])
                                    ->where('user_integral','>=',$coupon->conversion_price)
                                    ->decrement('user_integral',$coupon->conversion_price);
            if($integralAffected <= 0){
                DB::rollBack();
                return $this->error( __('api.exchange_null'));
            }

            $effects = DB::table('user_exchange')->insert([
                'coupon_id'=> $data['coupon_id'],
                'user_id' => $data['user_id'],
                'exchange_status' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            if(empty($effects)){
                DB::rollBack();
                return $this->error( __('api.exchange_error'));
            }

            $currentTime = now()->format('Y-m-d H:i'); 
            $Message = '你已於'.$currentTime.'兌換了 '.$coupon->coupon_name.'在'.$coupon->expiration_time.'過期請盡快使用!';
            $en_Message = 'You redeemed the '.$coupon->en_coupon_name.' on '.$currentTime.' and it will expire on '.$coupon->expiration_time.', Please use it as soon as possible !';
            
            //加入系統消息
            $SystemInfo = new SystemInfo();
            $code=$SystemInfo->addSystemInfo($userinfo->user_id, 3, $Message, $en_Message);

            $IntegralRecord = DB::table('user_integral_record')->insert([
                                                            'user_id'=>$data['user_id'],
                                                            'integral_type'=>2,
                                                            'integral_explain'=>'用戶積分兌換優惠券',
                                                            'en_integral_explain'=>'User points are redeemed for coupons',
                                                            'integral_number'=>$coupon->conversion_price,
                                                            'created_at'=>date('Y-m-d H:i:s'),
                                                            'updated_at'=>date('Y-m-d H:i:s')
                                                            ]);
            // $this->LogInfo('添加用戶購物車' , $effects , 'addUserCart' , true);//开启日志记录
            DB::commit();
            return $this->success( __('api.exchange_success'),$IntegralRecord);
        }catch (\Exception $e) {
            DB::rollBack();
            return $this->error( __('api.exchange_error') , $e->getMessage());
        }
    }




    /**
     * @param $user
     * @param $data
     * @return array
     * 獲取用戶已有優惠券列表
     */
    public function getUserHaveCoupon($data){
        $limit = empty($data['limit']) ? 10 : $data['limit'];
        $page =  ( $data['page'] - 1 ) * $limit;
        $where[] = array('coupon.coupon_status','=',1);
        $where[] = array('e.exchange_status','=',1);
        $where[] = array('e.user_id','=',$data['user_id']);
        $effects=array();
        $info = $this->join('user_exchange as e','e.coupon_id','=','coupon.coupon_id') ->where($where)
                    ->where(function ($query){
                        return  $query->where('coupon.expiration_time','>=',date('Y-m-d H:i:s'));
                    })->select($this->PrefixAppDetail)->orderBy('coupon.coupon_id','desc')->paginate($limit , $page);
        $effects=$info->items();
        foreach ($effects as $key => $value) {
            switch ($effects[$key]['coupon_type']) {
                case 1:
                    $effects[$key]['typeName'] = app('global_language') == "en"? 'Cash Coupon' : '現金券';
                    break;
                case 2:
                    $effects[$key]['typeName'] = app('global_language') == "en"? 'Discount Coupon' : '滿減券';
                    break;
                case 3:
                    $effects[$key]['typeName'] = app('global_language') == "en"? 'Instant Coupon' : '立折券';
                    $effects[$key]['coupon_discount'] = ($effects[$key]['coupon_discount']) * 10;
                    break;
                case 4:
                    $effects[$key]['typeName'] = app('global_language') == "en"? 'Full Coupon' : '滿折券';
                    $effects[$key]['coupon_discount'] = ($effects[$key]['coupon_discount']) * 10;
                    break;
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
     * @param $user
     * @param $data
     * @return array
     * 獲取用戶已有且可用優惠券列表
     */
    public function  getUsercheckCoupon($data){
        $limit = empty($data['limit']) ? 10 : $data['limit'];
        $page =  ( $data['page'] - 1 ) * $limit;
        $where[] = array('coupon.coupon_status','=',1);
        $where[] = array('e.exchange_status','=',1);
        $where[] = array('e.user_id','=',$data['user_id']);
        $where[] = array('coupon.lowest','<=',$data['money']);
        $effects=array();
        $info = $this->join('user_exchange as e','e.coupon_id','=','coupon.coupon_id') ->where($where)
                    ->where(function ($query){
                        return  $query->where('coupon.expiration_time','>=',date('Y-m-d H:i:s'));
                    })->select($this->PrefixAppDetail)->orderBy('coupon.coupon_id','desc')->paginate($limit , $page);
        $effects=$info->items();
        foreach ($effects as $key => $value) {
            switch ($effects[$key]['coupon_type']) {
                case 1:
                    $effects[$key]['typeName'] = app('global_language') == "en"? 'Cash Coupon' : '現金券';
                    break;
                case 2:
                    $effects[$key]['typeName'] = app('global_language') == "en"? 'Cash Coupon' : '現金券';
                    break;
                case 3:
                    $effects[$key]['typeName'] = app('global_language') == "en"? 'Discount Coupon' : '折扣券';
                    $effects[$key]['coupon_discount'] = ($effects[$key]['coupon_discount']) * 10;
                    break;
                case 4:
                    $effects[$key]['typeName'] = app('global_language') == "en"? 'Discount Coupon' : '折扣券';
                    $effects[$key]['coupon_discount'] = ($effects[$key]['coupon_discount']) * 10;
                    break;
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






/**************************************************common(api和admin共用)***********************************************************/




    /**
     * @param $data
     * @return array
     * 獲取優惠券快照
     */
    public function CouponSnapshot($coupon_id){
        $where[] = array('coupon_id','=',$coupon_id);
        $effects = $this ->where($where)->select('*')->first();
        //轉json
        return  json_encode($effects);
    }






    /**
     * @param $user
     * @param $data
     * @return array
     * 订单检测优惠券是否可用
     * data['user_id']
     * data['coupon_id']
     * data['money']
     */
    public function  OrderCheckCoupon($data){
        $where[] = array('coupon.coupon_status','=',1);
        $where[] = array('e.exchange_status','=',1);
        $where[] = array('e.user_id','=',$data['user_id']);
        $where[] = array('coupon.lowest','<=',$data['original_cost']);
        $where[] = array('coupon.coupon_id','=',$data['coupon_id']);
        $effects = $this->join('user_exchange as e','e.coupon_id','=','coupon.coupon_id') ->where($where)
                    ->where(function ($query){
                        return  $query->where('coupon.expiration_time','>=',date('Y-m-d H:i:s'));
                    })->select('coupon.coupon_id')->first();
        if(!empty($effects)){
             // $this->LogInfo('獲取收貨地址列表' , $effects , 'getUserAddress' , true);//开启日志记录
            return true;
        }else{
            return false;
        }
    }








}




