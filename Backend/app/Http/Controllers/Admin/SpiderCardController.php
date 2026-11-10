<?php
/*
 * File: SpiderCardController.php
 * Project: NewDemo
 * Created Time: 2026-06-08 04:14:24 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2026-06-29 10:50:17 am
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2026 成都睿創科技有限公司
 */




namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CardSort;
use App\Models\TradingCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SpiderCardController extends Controller
{
    protected $TradingCard;

    public function __construct()
    {
        $this->TradingCard = new TradingCard();
    }

    /**
     * 獲取交易卡片列表（取自 spider_cards 表）
     * @param Request $request
     * @return array
     */
    public function getSpiderCardListWeb(Request $request){
        $data = $request->post();
        $limit = $data['limit'] ?? 10;
        $page = ($data['page'] - 1) * $limit;

        $model = $this->TradingCard;

        // 搜索關鍵詞
        if (!empty($data['card_name'])) {
            $model = $model->where('card_name', 'like', '%' . $data['card_name'] . '%');
        }
        // 成色篩選（精確匹配）
        if (!empty($data['deal_condition'])) {
            $model = $model->where('deal_condition', $data['deal_condition']);
        }

        $count = $model->count();
        $query = $model->offset($page)->limit($limit);

        // 排序處理
        $sortBy = $data['sort_by'] ?? '';
        $sortOrder = $data['sort_order'] ?? 'desc';
        if ($sortBy === 'deal_price' && in_array($sortOrder, ['asc', 'desc'], true)) {
            $query->orderBy('deal_price', $sortOrder);
        } else {
            $query->orderBy('id', 'desc');
        }

        $list = $query->get()->toArray();

        // 字段映射：遠端 DB 欄位已用 card_ 前綴，只需補齊不存在或名稱不同的字段
        foreach ($list as &$item) {
            // card_img 已在 DB 中，若無值則從 condition_prices 取最高 PSA 價格對應的圖片 URL（暫無）
            $item['card_img'] = $item['card_img'] ?? '';
            $item['card_name'] = $item['card_name'] ?? $item['title'] ?? '';
            $item['card_brand'] = $item['card_brand'] ?? '';
            $item['card_category'] = $item['card_category'] ?? '';
            $item['card_type'] = $item['card_type'] ?? '';
            $item['card_code'] = $item['card_code'] ?? '';
            $item['card_describe'] = $item['card_describe'] ?? '';
            $item['is_update'] = $item['is_update'] ?? 0;
            // deal_price 從 card_cost 取得（min_price 不存在）
            $price = (float)($item['deal_price'] ?? ($item['card_cost'] ?? 0));
            $item['deal_price'] = $price;
            $item['card_price'] = $price;
            // 成色欄位直接使用數據庫 deal_condition 字段
            $item['conditions'] = $item['deal_condition'] ?? '';
        }

        $datas = [
            'count' => $count,
            'data' => $list
        ];

        return $this->success(__('web.success'), $datas);
    }

    /**
     * 新增卡牌
     * @param Request $request
     * @return array
     */
    public function addSpiderCardWeb(Request $request){
        $data = $request->post();

        $insert = [
            'card_name'       => $data['card_name'] ?? '',
            'en_card_name'    => $data['en_card_name'] ?? '',
            'card_brand'      => $data['card_brand'] ?? '',
            'card_category'   => $data['card_category'] ?? '',
            'card_type'       => $data['card_type'] ?? 'single',
            'card_code'       => $data['card_code'] ?? '',
            'card_cost'       => $data['card_cost'] ?? 0,
            'deal_price'      => $data['deal_price'] ?? 0,
            'deal_condition'  => $data['deal_condition'] ?? '',
            'card_img'        => $data['card_img'] ?? '',
            'card_describe'   => $data['card_describe'] ?? '',
            'crawler_url'     => $data['crawler_url'] ?? '',
            'crawler_id'      => $data['crawler_id'] ?? '',
            'deal_format'     => $data['deal_format'] ?? '',
            'is_update'       => 0,
            'created_at'      => date('Y-m-d H:i:s'),
            'updated_at'      => date('Y-m-d H:i:s'),
        ];

        $id = DB::table('spider_cards')->insertGetId($insert);

        if ($id) {
            return $this->success(__('web.success'), ['id' => $id]);
        } else {
            return $this->error(__('web.network_error'));
        }
    }

    /**
     * 編輯卡牌（修改價格）
     * @param Request $request
     * @return array
     */
    public function editSpiderCardWeb(Request $request){
        $data = $request->post();
        $id = $data['id'] ?? 0;
        if (empty($id)) {
            return $this->parametersError(__('api.param_error'));
        }

        $update = [
            'deal_price' => $data['deal_price'] ?? 0,
            'is_update' => $data['is_update'] ?? 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if (isset($data['card_img'])) {
            $update['card_img'] = $data['card_img'];
        }

        $result = DB::table('spider_cards')->where('id', $id)->update($update);

        // 同步更新 card 表中引用此 spider 卡的數據
        $newPrice = (float)($data['deal_price'] ?? 0);
        $cardUpdate = [];

        // 價格同步（基准價 = 修改後的 deal_price）
        if ($newPrice > 0) {
            $recyclePercent = 0;
            $shopSetting = DB::table('shop_setting')->where('shop_id', 1)->first();
            if ($shopSetting && isset($shopSetting->card_recycle_percent)) {
                $recyclePercent = (int)$shopSetting->card_recycle_percent;
            }
            $recyclePrice = round($newPrice * $recyclePercent / 100, 2);
            $cardUpdate = [
                'card_cost'    => $newPrice,
                'card_price'   => $recyclePrice,
                'card_recycle' => $recyclePrice,
            ];
        }

        // 圖片同步（只要有傳 card_img 就同步，不管價格有沒有變）
        if (isset($data['card_img'])) {
            $cardUpdate['card_img'] = $data['card_img'];
        }

        // 統一寫入 card 表
        $affectedSortIds = [];
        if ($result !== false && !empty($cardUpdate)) {
            $cardUpdate['updated_at'] = date('Y-m-d H:i:s');
            DB::table('card')
                ->where('trading_card_id', $id)
                ->where('card_status', '<>', -1)
                ->update($cardUpdate);
        }

        // 同步產品表（product.spider_cards_id 關聯；product_price 按原加價比例換算）
        if ($result !== false && $newPrice > 0) {
            DB::table('product')
                ->where('spider_cards_id', $id)
                ->where('product_cost', '>', 0)
                ->update([
                    'product_price' => DB::raw("ROUND(product_price * {$newPrice} / product_cost, 2)"),
                    'product_cost'  => $newPrice,
                    'updated_at'    => date('Y-m-d H:i:s'),
                ]);
        }

        // 重算包含此卡的卡池抽卡價格（管理員明確改價，立即生效）
        if ($result !== false) {
            try {
                $affectedSortIds = DB::table('card')
                    ->where('trading_card_id', $id)
                    ->where('card_status', '<>', -1)
                    ->whereNotNull('card_sort_id')
                    ->distinct()
                    ->pluck('card_sort_id')
                    ->toArray();
                foreach ($affectedSortIds as $sortId) {
                    // force=false：尊重卡池「每日自動更新」開關，關閉的池不被連帶重算
                    CardSort::recalcAndSaveDrawPrice((int)$sortId, false);
                }
            } catch (\Throwable $e) {
                // 重算失敗不影響主流程
            }
        }

        if ($result !== false) {
            return $this->success(__('web.success'));
        } else {
            return $this->error(__('web.network_error'));
        }
    }

    /**
     * 刪除卡牌（軟刪除）
     * @param Request $request
     * @return array
     */
    public function deleteSpiderCardWeb(Request $request){
        $ids = $request->post('ids', []);
        if (empty($ids)) {
            return $this->parametersError(__('api.param_error'));
        }
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        $ids = array_map('intval', $ids);
        $ids = array_filter($ids);

        DB::table('spider_cards')->whereIn('id', $ids)->delete();

        return $this->success(__('web.success'));
    }
}
