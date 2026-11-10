<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/7/1
 * Time: 17:07
 */

namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use App\Models\ShopFAQ;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    protected $FAQ;

    public function __construct()
    {
        $this->FAQ = new ShopFAQ();
    }

    /**
     * 獲取常見問題列表
     * @param Request $request
     * @return mixed
     */
    public function getFaqListWeb(Request $request){
        $data = $request->post();
        $language = self::getLanguageWeb();
        $datas = $this->FAQ->getFaqListWeb($data,$language);
        return $datas;
    }

    /**
     * 添加/編輯常見問題
     * @param Request $request
     * @return array
     */
    public function handleFaqWeb(Request $request){
        $data = $request->post();
        $state = $this->FAQ->handleFaqWeb($data);
        return $state;
    }

    /**
     * 刪除常見問題
     * @param Request $request
     * @return array
     */
    public function deleteFaqWeb(Request $request){
        $FAQID = $request->post('faq_id');
        $data = $this->FAQ->deleteFaqWeb($FAQID);
        return $data;
    }

}
