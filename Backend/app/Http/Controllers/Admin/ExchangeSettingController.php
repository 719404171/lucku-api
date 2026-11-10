<?php

/*
 * File: ExchangeSettingController.php
 * Project: NewDemo
 * Created Time: 2026-05-28 10:18:04 am
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2026-05-28 2:44:30 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2026 成都睿創科技有限公司
 */


namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExchangeSetting;
use Illuminate\Http\Request;

class ExchangeSettingController extends Controller
{
    protected $ExchangeSetting;

    public function __construct()
    {
        $this->ExchangeSetting = new ExchangeSetting();
    }

    /**
     * 後台-獲取兌換設置列表
     */
    public function getExchangeListWeb(Request $request)
    {
        $data = $request->post();
        $datas = $this->ExchangeSetting->getExchangeListWeb($data);
        return $this->success(__('web.success'), $datas);
    }

    /**
     * 後台-添加/編輯兌換設置
     */
    public function handleExchangeWeb(Request $request)
    {
        $data = $request->post();
        if (!isset($data['amount']) || !isset($data['exchange_coin'])) {
            return $this->error(__('web.param_error'));
        }
        $datas = $this->ExchangeSetting->handleExchangeWeb($data);
        return $datas;
    }

    /**
     * 後台-刪除兌換設置
     */
    public function deleteExchangeWeb(Request $request)
    {
        $exchangeId = $request->post('exchange_id');
        $datas = $this->ExchangeSetting->deleteExchangeWeb($exchangeId);
        return $datas;
    }

    /**
     * 後台-獲取所有啟用的兌換方案（前端下拉/列表使用）
     */
    public function getAllExchangeWeb()
    {
        $list = $this->ExchangeSetting->where('status', 1)
            ->orderBy('sort', 'asc')
            ->orderBy('exchange_id', 'desc')
            ->get()
            ->toArray();
        return $this->success(__('web.success'), $list);
    }
}
