<?php

/*
 * File: ExchangeSettingController.php
 * Project: NewDemo
 * Created Time: 2026-05-28 11:17:39 am
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2026-05-28 4:37:05 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2026 成都睿創科技有限公司
 */
namespace App\Http\Controllers\Api;

use App\Models\ExchangeSetting;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ExchangeSettingController extends Controller
{
    protected $ExchangeSetting;

    public function __construct()
    {
        $this->ExchangeSetting = new ExchangeSetting();
    }




 
    /**
     * @param Request $request
     * @return array
     *獲取啟用的兌換方案列表（前台/APP使用）
     */
    public function getExchangeSettings(Request $request)
    {
        $page = $request->post('page', 1);
        $limit = $request->post('limit', 10);
        $offset = ($page - 1) * $limit;

        $model = $this->ExchangeSetting->where('status', 1);

        $count = $model->count();

        $list = $model->orderBy('amount', 'asc')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->toArray();

        // 當前用戶在各檔位的首充領取狀態：APP 據此顯示「首充贈送」標籤或灰態提示
        $claimedMap = [];
        try {
            $authUser = auth('api')->user();
            if (!empty($authUser) && \App\Models\Base::hasTableCached('user_recharge_bonus') && !empty($list)) {
                $ids = array_column($list, 'exchange_id');
                $claimedMap = DB::table('user_recharge_bonus')
                    ->where('user_id', $authUser->user_id)
                    ->whereIn('exchange_id', $ids)
                    ->pluck('bonus_coin', 'exchange_id')
                    ->toArray();
            }
        } catch (\Throwable $e) {
            // 未登入、表不存在等情況：保守返回空 map，前端一律顯示可領取
        }
        foreach ($list as &$row) {
            $eid = (int)$row['exchange_id'];
            $row['bonus_claimed'] = isset($claimedMap[$eid]) ? 1 : 0;
            // 當前用戶在該檔位可實際領取的優惠金額（未領過=bonus_coin，已領過=0）
            $row['bonus_available'] = $row['bonus_claimed'] ? 0 : (int)($row['bonus_coin'] ?? 0);
        }
        unset($row);

        return $this->success(__('api.list_success'), [
            'data'  => $list,
            'count' => $count,
            'page'  => (int)$page,
            'limit' => (int)$limit,
        ]);
    }
}
