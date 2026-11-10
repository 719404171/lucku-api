<?php
/*
 * File: CardCardOrderController.php
 * Project: NewDemo
 * Created Time: 2026-06-03 11:07:21 am
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2026-06-03 4:19:50 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2026 成都睿創科技有限公司
 */



namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use App\Models\CardOrder;
use App\Models\CardOrderDetails;
use App\Models\CardOrderLogistics;
use App\Models\ShopSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CardOrderController extends Controller
{
    protected $CardOrder;
    protected $CardOrderDetails;
    protected $CardOrderLogistics;
    protected $CardOrderComment;

    public function __construct()
    {
        $this->CardOrder = new CardOrder();
        $this->CardOrderDetails = new CardOrderDetails();
        $this->CardOrderLogistics = new CardOrderLogistics();
    }

    /**
     * 獲取訂單列表
     * @param Request $request
     * @return array
     */
    public function getCardOrderListWeb(Request $request){
        $data = $request->post();
        $language = self::getLanguageWeb();
        $datas = $this->CardOrder->getOrderListWeb($data);
        return $this->success(__('web.success'),$datas);
    }



    /**
     * 添加訂單物流
     * @param Request $request
     * @return array
     */
    public function addCardOrderLogisticsWeb(Request $request){
        $data = $request->post();
        $datas = $this->CardOrderLogistics->addCardOrderLogisticsWeb($data);
        return $datas;
    }

    /**
     * 修改预约时间
     * @param Request $request
     * @return array
     */
    public function editCardOrderTimeWeb(Request $request){
        $token = $request->header('TOKEN');
        $data = $request->post();
        $datas = $this->CardOrder->editCardOrderTimeWeb($data,$token);
        return $datas;
    }

    /**
     * 修改订单支付时间(修改订单支付時間/与订单详情同步)后缀?CardOrder=true
     * @param Request $request
     * @return array
     */
    public function editPaymentTime(Request $request){
        $datas = $request->post();
        $data = $this->CardOrder->editPaymentTime($datas);
        return $data;
    }

    /**
     * 确认完成/確認收貨
     * @param Request $request
     * @return array
     */
    public function confirmReceiptWeb(Request $request){
        $token = $request->header('TOKEN');
        $data = $request->post();
        $datas = $this->CardOrderDetails->confirmReceiptWeb($data,$token);
        return $datas;
    }

    /**
     * 确认支付
     * @param Request $request
     * @return array
     */
    public function confirmPaymentWeb(Request $request){
        $token = $request->header('TOKEN');
        $CardOrderID = $request->post('card_order_id');
        $data = $this->CardOrder->confirmPaymentWeb($CardOrderID,$token);
        return $data;
    }

    /**
     * 已兌換卡片列表（篩選：用戶/時間/價格區間/卡牌名）
     * @param Request $request
     * @return array
     */
    public function getExchangedCardsWeb(Request $request){
        $data = $request->post();
        return $this->CardOrderDetails->getExchangedCardsWeb($data);
    }

    /**
     * 恢復已兌換卡片（扣除用戶點數，前提：點數充足）
     * @param Request $request
     * @return array
     */
    public function restoreExchangedCardsWeb(Request $request){
        $token = $request->header('TOKEN');
        $data = $request->post();
        return $this->CardOrderDetails->restoreExchangedCardsWeb($data, $token);
    }

    /**
     * Summary of ConfirmBooking
     * @param Request $request
     * @return array
     */
    public function scanConfirmCardOrderWeb(Request $request){
        if(empty($request->post('order_no'))){
            return $this->parametersError(__('api.param_error'));
        }else{
            $data = $request->post();
            $token = $request->header('token');
            $results = $this->CardOrder->scanConfirmCardOrderWeb($data,$token);
            return $results;
        }
    }

    /**
     * 刪除訂單
     * @param Request $request
     * @return array
     */
    public function deleteCardOrderWeb(Request $request){
        $token = $request->header('TOKEN');
        $CardOrderID = $request->post('card_order_id');
        $data = $this->CardOrder->deleteCardOrderWeb($CardOrderID,$token);
        return $data;
    }















}
