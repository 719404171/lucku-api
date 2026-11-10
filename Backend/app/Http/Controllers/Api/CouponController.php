<?php
/*
 * @Description:
 * @Version: 2.0
 * @Autor: wangyuqiang
 * @Date: 2020-12-16 11:18:25
 * @LastEditors: wangyuqiang
 * @LastEditTime: 2021-04-06 11:24:43
 */
namespace App\Http\Controllers\Api;

use App\Models\Coupon;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;



class CouponController extends Controller{

    private $Coupon;
    public function __construct()
    {
        $this->Coupon = new Coupon();
    }




    /**
     * @param Request $request
     * @return array
     * 獲取優惠券列表
     */
    public function getAllCoupons(Request $request){
        if(empty($request->post('page'))||empty($request->post('limit'))){
            return $this->parametersError(__('api.param_error'));
        }else{
            $data = $request->post();
            $user = auth('api')->user();
            $data['user_id']=empty($user) ? '' :  $user->user_id;
            $results = $this->Coupon->getAllCoupons($data);
            return $results;
        }
    }


    /**
     * @param Request $request
     * @return array
     * 兌換優惠券
     */
    public function exchangeCoupon(Request $request){
        if( empty($request->post('coupon_id'))   ){
            return $this->parametersError(__('api.param_error'));
        }else{
            $data = $request->post();
            \Log::info('Controller - coupon_id', [
                'value' => $data['coupon_id'] ?? 'null',
                'type'  => gettype($data['coupon_id'] ?? 'null')
            ]);
            $user = auth('api')->user();
            $data['user_id']=empty($user) ? '' :  $user->user_id;
            $results = $this->Coupon->exchangeCoupon($data);
            return $results;
        }
    }


    /**
     * @param Request $request
     * @return array
     * 獲取用戶已有優惠券列表
     */
    public function getUserHaveCoupon(Request $request){
        if(empty($request->post('page'))||empty($request->post('limit'))){
            return $this->parametersError(__('api.param_error'));
        }else{
            $data = $request->post();
            $user = auth('api')->user();
            $data['user_id']=empty($user) ? '' :  $user->user_id;
            $results = $this->Coupon->getUserHaveCoupon($data);
            return $results;
        }
    }





    /**
     * @param Request $request
     * @return array
     * 獲取用戶已有且可用優惠券列表
     */
    public function getUsercheckCoupon(Request $request){
        if(empty($request->post('page'))||empty($request->post('limit'))){
            return $this->parametersError(__('api.param_error'));
        }else{
            $data = $request->post();
            $user = auth('api')->user();
            $data['user_id']=$user->user_id;
            $data['money']=empty( $request->post('money') )? 0: $request->post('money');
            $results = $this->Coupon->getUsercheckCoupon($data);
            return $results;
        }
    }















}
