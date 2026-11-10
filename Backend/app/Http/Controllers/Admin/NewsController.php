<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/7/3
 * Time: 14:09
 */

namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    protected $News;

    public function __construct()
    {
        $this->News = new News();
    }

    /**
     * 获取咨询列表
     * @param Request $request
     * @return array
     */
    public function getNewsListWeb(Request $request){
        $data = $request->post();
        $language = self::getLanguageWeb();
        $datas = $this->News->getNewsListWeb($data,$language);
        return $datas;
    }

    /**
     * 添加/編輯咨询
     * @param Request $request
     * @return array
     */
    public function handleNewsWeb(Request $request){
        $data = $request->post();
        $datas = $this->News->handleNewsWeb($data);
        return $datas;
    }

    /**
     * 刪除咨询
     * @param Request $request
     * @return array
     */
    public function deleteNewsWeb(Request $request){
        $newsID = $request->post('news_id');
        $data = $this->News->deleteNewsWeb($newsID);
        return $data;
    }

}
