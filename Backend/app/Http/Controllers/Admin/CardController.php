<?php
/*
 * File: CardController.php
 * Project: NewDemo
 * Created Time: 2026-06-03 11:07:21 am
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2026-06-03 3:34:22 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2026 成都睿創科技有限公司
 */
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Card;
use Illuminate\Http\Request;

class CardController extends Controller
{
    protected $Card;

    public function __construct()
    {
        $this->Card = new Card();
    }

    /**
     * 獲取產品列表
     * @param Request $request
     * @return array
     */
    public function getCardListWeb(Request $request){
        $data = $request->post();
        $language = self::getLanguageWeb();
        $datas = $this->Card->getCardListWeb($data,$language);
        return $this->success(__('web.success'),$datas);
    }

    /**
     * 添加產品
     * @param Request $request
     * @return array
     */
    public function addCardWeb(Request $request){
        $Card = $request->post();
        $token = $request->header('TOKEN');
        $data = $this->Card->addCardWeb($Card,$token);
        return $data;
    }

    /**
     * 編輯產品
     * @param Request $request
     * @return array
     */
    public function editCardWeb(Request $request){
        $token = $request->header('TOKEN');
        $Card = $request->post();
        $data = $this->Card->editCardWeb($Card,$token);
        return $data;
    }

    /**
     * 刪除產品
     * @param Request $request
     * @return array
     */
    public function deleteCardWeb(Request $request){
        $CardID = $request->post('card_id');
        $data = $this->Card->deleteCardWeb($CardID);
        return $data;
    }

    /**
     * 獲取產品規格
     * @param Request $request
     * @return array
     */
    public function getCardSpecsWeb(Request $request){
        $CardID = $request->post('card_id');
        $data = $this->Card->getCardSpecsWeb($CardID);
        return $data;
    }

    /**
     * 添加產品規格組合
     * @param Request $request
     * @return array
     */
    public function addCardSpecsKeyWeb(Request $request){
        $specsKey = $request->post('specs_key');
        $CardID = $request->post('card_id');
        $language = self::getLanguageWeb();
        $datas = $this->CardSpecsKey->addCardSpecsKeyWeb($specsKey,$CardID,$language);
        return $datas;
    }

    /**
     * 編輯產品規格
     * @param Request $request
     * @return array
     */
    public function editCardSpecsWeb(Request $request){
        $token = $request->header('TOKEN');
        $CardRelation = $request->post('card_relation');
        $Card = $request->except(['card_relation']);
        $data = $this->Card->editCardSpecsWeb($CardRelation,$Card,$token);
        return $data;
    }

    /**
     * 獲取所有產品及規格
     * @return array
     */
    public function getAllCardWeb(){
        $language = self::getLanguageWeb();
        $data = $this->Card->getAllCardWeb($language);
        return $this->success(__('web.success'),$data);
    }

}
