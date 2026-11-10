<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentRechargeLog;
use Illuminate\Http\Request;

/**
 * 用戶充值記錄（後台查詢）
 */
class PaymentRechargeLogController extends Controller
{
    protected $PaymentRechargeLog;

    public function __construct()
    {
        $this->PaymentRechargeLog = new PaymentRechargeLog();
    }

    /**
     * 獲取用戶充值記錄列表
     * @param Request $request
     * @return array
     */
    public function getRechargeLogListWeb(Request $request){
        $data = $request->post();
        $datas = $this->PaymentRechargeLog->getRechargeLogListWeb($data);
        return $this->success(__('web.success'),$datas);
    }
}
