<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/7/1
 * Time: 14:21
 */

namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    protected $Coupon;

    public function __construct()
    {
        $this->Coupon = new Coupon();
    }

    /**
     * 獲取優惠券列表
     * @param Request $request
     * @return array
     */
    public function getCouponListWeb(Request $request){
        $data = $request->post();
        $language = self::getLanguageWeb();
        $datas = $this->Coupon->getCouponListWeb($data,$language);
        return $this->success(__('web.success'),$datas);
    }

    /**
     * 編輯/添加優惠券列表
     * @param Request $request
     * @return array
     */
    public function handleCouponListWeb(Request $request){
        $data = $request->post();
        $datas = $this->Coupon->handleCouponListWeb($data);
        return $datas;
    }

    /**
     * 刪除優惠券
     * @param Request $request
     * @return array
     */
    public function deleteCouponWeb(Request $request){
        $couponID = $request->post('coupon_id');
        $data = $this->Coupon->deleteCouponWeb($couponID);
        return $data;
    }

}
