<?php
/*
 * @Description:
 * @Version: 2.0
 * @Autor: wangyuqiang
 * @Date: 2020-12-16 11:18:25
 * @LastEditors: wangyuqiang
 * @LastEditTime: 2020-12-24 15:40:38
 */


namespace App\Http\Controllers\Api;

use App\Models\ShopService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\CreatesApplication;

class ShopServiceController extends Controller
{
    private $ShopService;
    public function __construct()
    {
        $this->ShopService = new ShopService();
    }


    /**
     * @param Request $request
     * @return array
     * 獲取客服設置列表
     */
    public function getShopServiceApi(Request $request){
        $results = $this->ShopService->getShopServiceApi();
        return $results;
    }



}
