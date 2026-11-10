<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/7/2
 * Time: 13:58
 */

namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use App\Models\ShopSetting;
use App\Models\WeekWorkday;
use Illuminate\Http\Request;

class ShopSettingController extends Controller
{
    protected $ShopSetting;

    public function __construct()
    {
        $this->ShopSetting = new ShopSetting();
    }
    /**
     * 获取店铺基础信息
     * @return array
     */
    public function getShopFoundationWeb(){
        $data = ShopSetting::where("shop_id",1)->select('shop_name','shop_logo')->first();
        return $this->success($data);
    }

    /**
     * 獲取店鋪信息
     * @return array
     */
    public function getShopSettingWeb(){
        $data = ShopSetting::where('shop_id',1)->first();
        $data['workday'] = WeekWorkday::getworkday();
        if(empty($data)){
            $data = [];
        }
        return $this->success(__('web.success'),$data);
    }

    /**
     * 編輯店鋪信息
     * @param Request $request
     * @return array
     */
    public function editShopSettingWeb(Request $request){
        $data = $request->post();
        $datas = $this->ShopSetting->editShopSettingWeb($data);
        return $datas;
    }

}
