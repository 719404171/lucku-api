<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/7/3
 * Time: 16:39
 */

namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use App\Models\ShopGeTui;
use Illuminate\Http\Request;

class ShopGeTuiController extends Controller
{
    protected $ShopGeTui;

    public function __construct()
    {
        $this->ShopGeTui = new ShopGeTui();
    }

    /**
     * 獲取推送配置
     * @return array
     */
    public function getShopGeTuiWeb(){
        $data = ShopGeTui::where('getui_id',1)->first();
        return $this->success(__('web.success'),$data);
    }

    /**
     * 添加/編輯推送配置
     * @param Request $request
     * @return array
     */
    public function handleShopGeTuiWeb(Request $request){
        $data = $request->post();
        $datas = $this->ShopGeTui->handleShopGeTuiWeb($data);
        return $datas;
    }

}
