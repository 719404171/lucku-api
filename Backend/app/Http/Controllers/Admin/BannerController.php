<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/7/1
 * Time: 16:46
 */

namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    protected $Banner;

    public function __construct()
    {
        $this->Banner = new Banner();
    }

    /**
     * 獲取Banner列表
     * @param Request $request
     * @return mixed
     */
    public function getBannerListWeb(Request $request){
        $data = $request->post();
        $language = self::getLanguageWeb();
        $datas = $this->Banner->getBannerListWeb($data,$language);
        return $datas;
    }

    /**
     * 添加/編輯Banner
     * @param Request $request
     * @return array
     */
    public function handleBannerListWeb(Request $request){
        $data = $request->post();
        $state = $this->Banner->handleBannerListWeb($data);
        return $state;
    }

    /**
     * 刪除Banner
     * @param Request $request
     * @return array
     */
    public function deleteBannerListWeb(Request $request){
        $bannerID = $request->post('banner_id');
        $data = $this->Banner->deleteBannerListWeb($bannerID);
        return $data;
    }

}
