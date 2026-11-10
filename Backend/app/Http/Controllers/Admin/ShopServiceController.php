<?php
/*
 * @Description:
 * @Version: 2.0
 * @Autor: wangyuqiang
 * @Date: 2020-12-04 14:32:37
 * @LastEditors: wangyuqiang
 * @LastEditTime: 2021-08-10 16:11:50
 */
namespace App\Http\Controllers\Admin;

use App\Models\ShopService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
class ShopServiceController extends Controller
{
    private $ShopService;
    public function __construct()
    {
        $this->ShopService = new ShopService();
    }

    /**
     * 獲取客服列表
     * @param Request $request
     * @return array
     */
    public function getShopServiceListWeb(Request $request){
        $data = $request->post();
        $datas = $this->ShopService->getShopServiceListWeb($data);
        return $datas;
    }

    /**
     * 添加/編輯客服信息
     * @param Request $request
     * @return array
     */
    public function handleShopServiceWeb(Request $request){
        $data = $request->post();
        $datas = $this->ShopService->handleShopServiceWeb($data);
        return $datas;
    }

    /**
     * 刪除客服信息
     * @param Request $request
     * @return array
     */
    public function deleteShopServiceWeb(Request $request){
        $serviceID = $request->post('service_id');
        $data = $this->ShopService->deleteShopServiceWeb($serviceID);
        return $data;
    }



}
