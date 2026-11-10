<?php
/*
 * File: CardSortController.php
 * Project: NewDemo
 * Created Time: 2026-06-04 04:11:10 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2026-07-02 10:16:59 am
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2026 成都睿創科技有限公司
 */




namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CardSort;
use App\Models\Card;
use App\Models\TradingCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CardSortController extends Controller
{
    protected $CardSort;

    public function __construct()
    {
        $this->CardSort = new CardSort();
    }

    /**
     * 安全轉字串，避免 Array to string conversion
     */
    private static function safeStr($val, $default = '')
    {
        if ($val === null || is_scalar($val)) {
            return (string)($val ?? $default);
        }
        if (is_array($val) || is_object($val)) {
            return $default;
        }
        return (string)$val;
    }

    /**
     * 從 TradingCard 對象安全讀取屬性
     */
    private static function safeTcAttr($tc, $attr, $default = '')
    {
        if ($tc === null) return $default;
        try {
            $val = $tc->$attr ?? null;
            return self::safeStr($val, $default);
        } catch (\Exception $e) {
            return $default;
        }
    }

    /**
     * 獲取分類列表
     * @param Request $request
     * @return array
     */
    public function getCardSortListWeb(Request $request){
        $data = $request->post();
        $language = self::getLanguageWeb();
        $datas = $this->CardSort->getCardSortListWeb($data,$language);
        return $this->success(__('web.success'),$datas);
    }

    /**
     * 獲取全部已維護過的卡池品牌（供卡池表單品牌下拉選取）
     * @param Request $request
     * @return array
     */
    public function getAllCardBrandsWeb(Request $request){
        return $this->CardSort->getAllCardBrands();
    }

    /**
     * 添加/編輯產品分類
     * @param Request $request
     * @return array
     */
    public function handleCardSortListWeb(Request $request){
        $data = $request->post();
        $datas = $this->CardSort->handleCardSortListWeb($data);
        return $datas;
    }

    /**
     * 刪除產品分類
     * @param Request $request
     * @return array
     */
    public function deleteCardSortListWeb(Request $request){
        $CardSortID = $request->post('card_sort_id');
        $data = $this->CardSort->deleteCardSortListWeb($CardSortID);
        return $data;
    }

    /**
     * 開啟/關閉單個卡池的抽卡入口
     * @param Request $request
     * @return array
     */
    public function editCardSortDrawEnableWeb(Request $request){
        $data = $request->post();
        return $this->CardSort->editCardSortDrawEnableWeb($data);
    }

    /**
     * 開啟/關閉單個卡池的「優惠卡包」標記
     * 開啟後每個帳號僅能抽一次，且 App 只顯示單抽按鈕
     */
    public function editCardSortOnceOnlyWeb(Request $request){
        $data = $request->post();
        return $this->CardSort->editCardSortOnceOnlyWeb($data);
    }

    /**
     * 一鍵開啟/關閉全部卡池的抽卡入口
     * @param Request $request
     * @return array
     */
    public function batchCardSortDrawEnableWeb(Request $request){
        $data = $request->post();
        return $this->CardSort->batchCardSortDrawEnableWeb($data);
    }

    /**
     * 獲取所有分類列表
     * @return array
     */
    public function getAllCardSortWeb(){
        $language = self::getLanguageWeb();
        $data = $this->CardSort->getAllCardSortWeb($language);
        return $data;
    }

    /**
     * 獲取所有分類商品
     * @param Request $request
     * @return array|null|string
     */
    public function getAllSortProductWeb(Request $request){
        $data = $request->post();
        $language = self::getLanguageWeb();
        $data = $this->CardSort->getAllSortProductWeb($data,$language);
        return $data;
    }

    /**
     * 獲取卡池關聯/未關聯卡片列表（支持搜索）
     * @param Request $request
     * @return array
     */
    public function getCardsBySortWeb(Request $request){
        $card_sort_id = $request->post('card_sort_id');
        $keyword = $request->post('keyword', '');
        if (empty($card_sort_id)) {
            return $this->parametersError(__('api.param_error'));
        }
        $language = self::getLanguageWeb();

        // 已關聯：屬於該卡池的卡片（使用 DB table 直接查詢，避免 Eloquent 模型處理）
        $associatedQuery = DB::table('card')
            ->leftJoin('spider_cards', 'card.trading_card_id', '=', 'spider_cards.id')
            ->where('card.card_sort_id', $card_sort_id)
            ->where('card.card_status', '<>', -1);
        if (!empty($keyword)) {
            $associatedQuery->where(function($q) use ($keyword) {
                $q->where('card.card_name', 'like', '%' . $keyword . '%')
                  ->orWhere('card.en_card_name', 'like', '%' . $keyword . '%');
            });
        }
        $associated = $associatedQuery->select('card.card_id', 'card.card_sort_id', 'card.card_name', 'card.en_card_name', 'card.card_img', 'card.card_price', 'card.card_cost', 'card.card_stock', 'card.card_tag', 'card.is_priority', 'spider_cards.deal_condition')
            ->selectRaw('card.card_price as deal_price')
            // 正價（與卡庫展示一致）：優先卡庫即時價 spider_cards.deal_price，否則用入池快照 card.card_cost
            ->selectRaw('COALESCE(NULLIF(spider_cards.deal_price, 0), card.card_cost, card.card_price, 0) as regular_price')
            ->orderBy('card_id', 'desc')
            ->get()
            ->map(function($v) { return (array)$v; })
            ->toArray();

        // 統計所有標籤（用於前端 tab 展示），優先使用已保存的順序
        $allTags = DB::table('card')
            ->where('card_sort_id', $card_sort_id)
            ->where('card_status', '<>', -1)
            ->whereNotNull('card_tag')
            ->distinct()
            ->pluck('card_tag')
            ->toArray();

        // 嘗試從 card_sort 表獲取已保存的標籤順序
        $cardSortRow = DB::table('card_sort')->where('card_sort_id', $card_sort_id)->first(['card_tag_order', 'card_tag_animations']);
        $savedOrder = $cardSortRow && $cardSortRow->card_tag_order
            ? json_decode($cardSortRow->card_tag_order, true)
            : null;
        // 標籤對應的中獎動畫（標籤=>動畫URL），未設置的標籤抽獎時用卡池默認動畫
        $tagAnimations = [];
        if ($cardSortRow && !empty($cardSortRow->card_tag_animations)) {
            $decoded = json_decode($cardSortRow->card_tag_animations, true);
            if (is_array($decoded)) {
                $tagAnimations = $decoded;
            }
        }

        if (!empty($savedOrder) && is_array($savedOrder)) {
            // 按已保存順序排列，並過濾掉已被刪除的標籤
            $orderedTags = array_values(array_intersect($savedOrder, $allTags));
            // 補上未在 savedOrder 中的標籤
            $missingTags = array_diff($allTags, $savedOrder);
            sort($missingTags);
            $tags = array_merge($orderedTags, $missingTags);
        } else {
            sort($allTags);
            $tags = $allTags;
        }

        // 確保「預設」永遠在第一個位置
        $visibleTags = array_values(array_filter($tags, function($t) {
            return $t !== '預設';
        }));
        array_unshift($visibleTags, '預設');

        // 統計每個標籤的總卡牌數和未抽取數（含「預設」）
        $tagStats = [];
        foreach ($visibleTags as $tag) {
            $totalQuery = DB::table('card')
                ->where('card_sort_id', $card_sort_id)
                ->where('card_status', '<>', -1);
            if ($tag === '預設') {
                $totalQuery->where(function($q) {
                    $q->whereNull('card_tag')->orWhere('card_tag', '')->orWhere('card_tag', '預設');
                });
            } else {
                $totalQuery->where('card_tag', $tag);
            }
            $total = $totalQuery->count();
            $undrawnQuery = DB::table('card')
                ->where('card_sort_id', $card_sort_id)
                ->where('card_status', '<>', -1)
                ->where('card_stock', '>', 0);
            if ($tag === '預設') {
                $undrawnQuery->where(function($q) {
                    $q->whereNull('card_tag')->orWhere('card_tag', '')->orWhere('card_tag', '預設');
                });
            } else {
                $undrawnQuery->where('card_tag', $tag);
            }
            $undrawn = $undrawnQuery->count();
            $tagStats[$tag] = [
                'total' => $total,
                'undrawn' => $undrawn
            ];
        }

        // 卡庫：取自 spider_cards 表（全部顯示，含已關聯）
        $unassociatedQuery = DB::table('spider_cards')->orderBy('id', 'desc');
        if (!empty($keyword)) {
            $unassociatedQuery->where('card_name', 'like', '%' . $keyword . '%');
        }
        $unassociated = $unassociatedQuery->select('id', 'card_name', 'card_img', 'card_brand', 'card_category', 'card_type', 'card_code', 'card_cost', 'deal_price', 'deal_condition', 'card_describe')
            ->get()
            ->map(function($v) { return (array)$v; })
            ->toArray();

        // 取得所有已關聯的 trading_card_id 集合（用於標記已關聯狀態）
        $associatedTradingIdSet = [];
        try {
            $associatedTradingIdSet = DB::table('card')
                ->whereNotNull('trading_card_id')
                ->where('card_status', '<>', -1)
                ->pluck('trading_card_id')
                ->map(function($v) { return (int)$v; })
                ->toArray();
            $associatedTradingIdSet = array_flip($associatedTradingIdSet);
        } catch (\Exception $e) {
            // 忽略
        }

        // 字段映射為前端統一的 card_ 前綴，並確保所有值為純量
        foreach ($unassociated as &$item) {
            $tid = (int)($item['id'] ?? 0);
            $rawPrice = (float)(($item['deal_price'] ?? 0) ?: ($item['card_cost'] ?? 0));
            $item['card_id'] = $tid;
            $item['card_img'] = self::safeStr($item['card_img'] ?? '');
            $item['card_name'] = self::safeStr($item['card_name'] ?? '');
            $item['card_price'] = $rawPrice;
            $item['deal_price'] = $rawPrice;
            $item['min_price'] = $rawPrice; // 向下相容
            $item['conditions'] = $item['deal_condition'] ?? '';
            $item['card_stock'] = 1;
            $item['is_associated'] = isset($associatedTradingIdSet[$tid]) ? 1 : 0;
        }
        unset($item);

        // 確保 response 數據不含非純量值（保留數值字段類型）
        $numericFields = ['card_id', 'card_price', 'card_cost', 'regular_price', 'deal_price', 'card_stock', 'card_sort_id', 'is_priority'];
        $safeAssociated = [];
        foreach ($associated as $a) {
            $a['conditions'] = $a['deal_condition'] ?? '';
            $safeItem = [];
            foreach ($a as $k => $v) {
                if (in_array($k, $numericFields)) {
                    $safeItem[$k] = is_numeric($v) ? $v + 0 : ($v ?? '');
                } else {
                    $safeItem[$k] = self::safeStr($v ?? '');
                }
            }
            $safeAssociated[] = $safeItem;
        }

        $results = [
            'associated' => $safeAssociated,
            'unassociated' => $unassociated,
            'tags' => $visibleTags,
            'tag_stats' => $tagStats,
            // 標籤對應的中獎動畫（標籤=>動畫URL），未設置的標籤抽獎時用卡池默認動畫
            'tag_animations' => $tagAnimations,
            // 抽卡價格公式統計：卡牌數量 / 卡牌總價（正價） / 手動百分比 / 自動更新開關 / 計算結果
            'sort_price' => CardSort::poolDrawPriceStats($card_sort_id)
        ];

        return $this->success(__('web.success'), $results);
    }

    /**
     * 保存卡池抽卡價格設置（手動百分比 / 是否自動更新），保存後立即按公式重算抽卡價格
     * @param Request $request
     * @return array
     */
    public function updateCardSortDrawPriceWeb(Request $request){
        return $this->CardSort->updatePoolDrawPriceSetting($request->post());
    }

    /**
     * 批量關聯/取消關聯卡片到卡池
     * @param Request $request
     * @return array
     */
    public function associateCardsWeb(Request $request){
        $card_sort_id = $request->post('card_sort_id');
        $rawCardIds = $request->post('card_ids', []);
        $items = $request->post('items', []); // [{card_id, count}]
        $action = $request->post('action', 'associate');

        // 支援 items 格式（含數量）
        $card_ids = [];
        $cardCounts = []; // card_id => count
        if (!empty($items) && is_array($items)) {
            foreach ($items as $item) {
                $id = (int)($item['card_id'] ?? 0);
                $count = max(1, (int)($item['count'] ?? 1));
                if ($id > 0) {
                    $card_ids[] = $id;
                    $cardCounts[$id] = $count;
                }
            }
        } else {
            // 相容舊格式 card_ids
            if (is_array($rawCardIds)) {
                foreach ($rawCardIds as $v) {
                    if (is_scalar($v)) {
                        $id = (int)$v;
                        $card_ids[] = $id;
                        $cardCounts[$id] = 1;
                    }
                }
            }
        }
        $card_ids = array_values(array_unique(array_filter($card_ids, function($v) { return $v > 0; })));

        if ($action === 'batch_priority') {
            $isPriority = (int)$request->post('is_priority', 0);
            if (!in_array($isPriority, [0, 1], true)) {
                return $this->parametersError(__('api.param_error'));
            }
            if (empty($card_ids)) {
                return $this->parametersError(__('api.param_error'));
            }
            DB::table('card')
                ->whereIn('card_id', $card_ids)
                ->where('card_status', '<>', -1)
                ->update(['is_priority' => $isPriority, 'updated_at' => now()]);
            return $this->success(__('web.success'));
        }

        if (empty($card_ids)) {
            return $this->parametersError(__('api.param_error'));
        }

        if ($action === 'associate') {
            if (empty($card_sort_id)) {
                return $this->parametersError(__('api.param_error'));
            }

            try {
                // 讀取卡牌設置（回收百分比）
                $recyclePercent = 0;
                $shopSetting = \Illuminate\Support\Facades\DB::table('shop_setting')
                    ->where('shop_id', 1)
                    ->first();
                if ($shopSetting && isset($shopSetting->card_recycle_percent)) {
                    $recyclePercent = (int)$shopSetting->card_recycle_percent;
                }

                // 讀取卡池名稱
                $language = self::getLanguageWeb();
                $sortNameField = $language === 'en_' ? 'en_card_sort_name' : 'card_sort_name';
                $cardSortName = DB::table('card_sort')
                    ->where('card_sort_id', $card_sort_id)
                    ->value($sortNameField) ?? '';

                $now = now();

                // 一律從 spider_cards 表建立新的卡片紀錄（允許重複關聯）
                $tcRows = DB::table('spider_cards')->whereIn('id', $card_ids)->get();
                foreach ($tcRows as $tr) {
                    $basePrice = (float)($tr->deal_price ?? $tr->card_cost ?? 0);
                    $trPrice = round($basePrice * $recyclePercent / 100, 2);
                    $trRecyclePrice = round($basePrice * $recyclePercent / 100, 2);
                    $copyCount = $cardCounts[$tr->id] ?? 1;
                    $cardRows = [];
                    for ($i = 0; $i < $copyCount; $i++) {
                        $cardRows[] = [
                            'card_sort_id'    => $card_sort_id,
                            'card_sort_name'  => $cardSortName,
                            'card_name'       => self::safeStr($tr->card_name ?? '') ?: self::safeStr($tr->en_card_name ?? ''),
                            'card_brand'      => self::safeStr($tr->card_brand ?? null),
                            'card_img'        => self::safeStr($tr->card_img ?? null),
                            'card_price'      => $trPrice,
                            'card_cost'       => $basePrice,
                            'card_describe'   => self::safeStr($tr->card_describe ?? null),
                            'card_recycle'    => $trRecyclePrice,
                            'card_stock'      => 1,
                            'card_status'     => 1,
                            'card_type'       => 1,
                            'is_priority'     => 0,
                            'trading_card_id' => $tr->id,
                            'crawler_id'      => (string)($tr->crawler_id ?? $tr->id ?? ''),
                            'crawler_url'     => self::safeStr($tr->url ?? $tr->crawler_url ?? null),
                            'card_category'   => self::safeStr($tr->card_category ?? null),
                            'deal_condition'  => self::safeStr($tr->deal_condition ?? null),
                            'created_at'      => $now,
                            'updated_at'      => $now,
                        ];
                    }
                    DB::table('card')->insert($cardRows);
                }
                // 關聯改變了卡牌數量/總價：卡池開啟自動更新時立即按公式重算抽卡價格
                CardSort::recalcAndSaveDrawPrice($card_sort_id, false);
            } catch (\Exception $e) {
                return $this->error(__('web.fail') . ': ' . $e->getMessage());
            }
        } else {
            // 取消關聯：將 card_sort_id 設為 null
            // 先取受影響卡池（取消關聯請求不帶 card_sort_id），更新後按開關重算抽卡價格
            $affectedSortIds = DB::table('card')
                ->whereIn('card_id', $card_ids)
                ->where('card_status', '<>', -1)
                ->whereNotNull('card_sort_id')
                ->pluck('card_sort_id')
                ->unique()
                ->map(function ($v) { return (int)$v; })
                ->toArray();
            DB::table('card')
                ->whereIn('card_id', $card_ids)
                ->where('card_status', '<>', -1)
                ->update(['card_sort_id' => null]);
            foreach ($affectedSortIds as $sid) {
                CardSort::recalcAndSaveDrawPrice($sid, false);
            }
        }

        return $this->success(__('web.success'));
    }

    /**
     * 更新卡片標籤分組（支援拖拽變更）
     * @param Request $request
     * @return array
     */
    public function updateCardTagWeb(Request $request){
        $card_id = $request->post('card_id');
        $card_tag = $request->post('card_tag', '預設');

        if (empty($card_id)) {
            return $this->parametersError(__('api.param_error'));
        }

        // 使用 DB table 查詢，避免 Eloquent 模型處理
        $card = DB::table('card')
            ->where('card_id', (int)$card_id)
            ->where('card_status', '<>', -1)
            ->first();

        if (!$card) {
            return $this->error(__('web.fail'));
        }

        $updateData = ['card_tag' => $card_tag, 'updated_at' => now()];

        // 若 trading_card_id 為空，嘗試從 card_name 或 card_img 查找 spider_cards
        $tcId = $card->trading_card_id ?? 0;
        if (empty($tcId)) {
            $tcRow = DB::table('spider_cards')
                ->where('card_name', self::safeStr($card->card_name ?? ''))
                ->orWhere('card_img', self::safeStr($card->card_img ?? ''))
                ->first(['id', 'card_brand', 'card_describe']);
            if ($tcRow) {
                $updateData['trading_card_id'] = $tcRow->id;
                if (empty($card->card_brand)) {
                    $updateData['card_brand'] = self::safeStr($tcRow->card_brand ?? null);
                }
                if (empty($card->card_describe)) {
                    $updateData['card_describe'] = self::safeStr($tcRow->card_describe ?? null);
                }
            }
        } elseif (empty($card->card_brand) || empty($card->card_describe)) {
            $tcRow = DB::table('spider_cards')->where('id', $tcId)->first(['card_brand', 'card_describe']);
            if ($tcRow) {
                if (empty($card->card_brand)) {
                    $updateData['card_brand'] = self::safeStr($tcRow->card_brand ?? null);
                }
                if (empty($card->card_describe)) {
                    $updateData['card_describe'] = self::safeStr($tcRow->card_describe ?? null);
                }
            }
        }

        DB::table('card')->where('card_id', (int)$card_id)->update($updateData);

        return $this->success(__('web.success'));
    }

    /**
     * 批量更新卡片標籤（一次請求處理數千張卡，替代逐卡請求）
     * @param Request $request
     * @return array
     */
    public function updateCardTagBatchWeb(Request $request){
        $card_ids = $request->post('card_ids', []);
        $card_tag = $request->post('card_tag', '預設');

        if (!is_array($card_ids) || empty($card_ids)) {
            return $this->parametersError(__('api.param_error'));
        }
        $card_tag = self::safeStr($card_tag);
        if ($card_tag === '') {
            return $this->parametersError(__('api.param_error'));
        }

        // 純數字 id，分批更新避免 SQL 過長
        $ids = array_values(array_filter(array_map('intval', $card_ids)));
        if (empty($ids)) {
            return $this->parametersError(__('api.param_error'));
        }

        // 補充資料：僅處理缺 trading_card_id 的卡（正常關聯入池的卡不會命中）
        $needEnrich = DB::table('card')
            ->whereIn('card_id', $ids)
            ->where('card_status', '<>', -1)
            ->where(function ($q) {
                $q->whereNull('trading_card_id')->orWhere('trading_card_id', 0);
            })
            ->get(['card_id', 'card_name', 'card_img', 'card_brand', 'card_describe']);
        if ($needEnrich->isNotEmpty()) {
            $names = $needEnrich->pluck('card_name')->filter()->unique()->values()->all();
            $imgs = $needEnrich->pluck('card_img')->filter()->unique()->values()->all();
            $tcRows = collect();
            if ($names || $imgs) {
                $tcRows = DB::table('spider_cards')
                    ->where(function ($q) use ($names, $imgs) {
                        if ($names) { $q->orWhereIn('card_name', $names); }
                        if ($imgs) { $q->orWhereIn('card_img', $imgs); }
                    })
                    ->get(['id', 'card_name', 'card_img', 'card_brand', 'card_describe']);
            }
            $byName = $tcRows->keyBy('card_name');
            $byImg = $tcRows->keyBy('card_img');
            foreach ($needEnrich as $c) {
                $tc = $byName->get($c->card_name) ?: $byImg->get($c->card_img);
                if (!$tc) {
                    continue;
                }
                $extra = ['trading_card_id' => $tc->id];
                if (empty($c->card_brand) && !empty($tc->card_brand)) {
                    $extra['card_brand'] = self::safeStr($tc->card_brand);
                }
                if (empty($c->card_describe) && !empty($tc->card_describe)) {
                    $extra['card_describe'] = self::safeStr($tc->card_describe);
                }
                DB::table('card')->where('card_id', $c->card_id)->update($extra);
            }
        }

        // 一次 SQL 完成標籤更新
        $updated = 0;
        foreach (array_chunk($ids, 1000) as $chunk) {
            $updated += DB::table('card')
                ->whereIn('card_id', $chunk)
                ->where('card_status', '<>', -1)
                ->update(['card_tag' => $card_tag, 'updated_at' => now()]);
        }

        return $this->success(__('web.success'), ['updated' => $updated]);
    }

    /**
     * 保存標籤順序
     * @param Request $request
     * @return array
     */
    public function saveCardTagOrderWeb(Request $request){
        $card_sort_id = $request->post('card_sort_id');
        $tags = $request->post('tags', []);

        if (empty($card_sort_id)) {
            return $this->parametersError(__('api.param_error'));
        }

        $cardSort = CardSort::find($card_sort_id);
        if (!$cardSort) {
            return $this->error(__('web.fail'));
        }

        $cardSort->card_tag_order = json_encode($tags);
        $cardSort->save();

        return $this->success(__('web.success'));
    }

    /**
     * 保存單個標籤的中獎動畫 URL（留空則清除，抽獎時該標籤使用卡池默認動畫）
     * @param Request $request
     * @return array
     */
    public function saveCardTagAnimationWeb(Request $request){
        $card_sort_id = $request->post('card_sort_id');
        $tag = (string)$request->post('tag', '');
        $animationUrl = (string)$request->post('animation_url', '');

        if (empty($card_sort_id) || $tag === '' || $tag === '預設') {
            return $this->parametersError(__('api.param_error'));
        }

        $cardSort = CardSort::find($card_sort_id);
        if (!$cardSort) {
            return $this->error(__('web.fail'));
        }

        $current = [];
        if (!empty($cardSort->card_tag_animations)) {
            $decoded = json_decode($cardSort->card_tag_animations, true);
            if (is_array($decoded)) {
                $current = $decoded;
            }
        }

        $animationUrl = trim($animationUrl);
        if ($animationUrl === '') {
            unset($current[$tag]); // 清除該標籤動畫，使用默認動畫
        } else {
            $current[$tag] = $animationUrl;
        }

        $cardSort->card_tag_animations = json_encode($current, JSON_UNESCAPED_UNICODE);
        $cardSort->save();

        return $this->success(__('web.success'), ['tag_animations' => $current]);
    }

    /**
     * 更新卡片是否展示（is_priority）
     * @param Request $request
     * @return array
     */
    public function updateCardPriorityWeb(Request $request){
        $card_id = $request->post('card_id');
        $is_priority = $request->post('is_priority');

        if (empty($card_id) || !in_array((int)$is_priority, [0, 1], true)) {
            return $this->parametersError(__('api.param_error'));
        }

        DB::table('card')
            ->where('card_id', (int)$card_id)
            ->where('card_status', '<>', -1)
            ->update(['is_priority' => (int)$is_priority, 'updated_at' => now()]);

        return $this->success(__('web.success'));
    }

    /**
     * 卡池初始化：複製一個設置/抽卡預設完全相同的新卡池（未被抽取過），
     * 原卡池軟刪除（status=-1），歷史訂單/背包等數據不受影響
     * @param Request $request
     * @return array
     */
    public function initCardSortWeb(Request $request){
        $card_sort_id = (int)$request->post('card_sort_id');
        if (empty($card_sort_id)) {
            return $this->parametersError(__('api.param_error'));
        }

        $src = DB::table('card_sort')
            ->where('card_sort_id', $card_sort_id)
            ->where('status', '<>', -1)
            ->first();
        if (empty($src)) {
            return $this->error(__('api.param_error'));
        }

        $now = date('Y-m-d H:i:s');
        try {
            DB::beginTransaction();

            // 1. 複製卡池本身（全部設置相同，僅 ID/時間不同）
            $sortData = (array)$src;
            unset($sortData['card_sort_id']);
            $sortData['created_at'] = $now;
            $sortData['updated_at'] = $now;
            $newSortId = DB::table('card_sort')->insertGetId($sortData);

            // 2. 複製所有未刪除的卡（card_status<>-1），不論是否已被抽取；
            //    新池視為從未被抽取過，統一將 card_stock 重置為 1。
            // 查詢包含 card_id 僅用於建立映射，插入時剔除
            $cardCols = Schema::getColumnListing('card');
            $oldCards = DB::table('card')
                ->where('card_sort_id', $card_sort_id)
                ->where('card_status', '<>', -1)
                ->orderBy('card_id')
                ->get($cardCols);
            foreach (array_chunk($oldCards->all(), 500) as $chunk) {
                $rows = [];
                foreach ($chunk as $c) {
                    $row = (array)$c;
                    unset($row['card_id']);
                    $row['card_sort_id'] = $newSortId;
                    $row['card_stock'] = 1; // 新池從未被抽取，庫存重置為初始值 1
                    $row['browse_number'] = 0; // 瀏覽量視為歷史數據，新池歸零
                    $row['created_at'] = $now;
                    $row['updated_at'] = $now;
                    $rows[] = $row;
                }
                DB::table('card')->insert($rows);
            }
            // 新池卡與原池卡均按 card_id 升序一一對應，建立 old => new 映射
            $oldIds = $oldCards->pluck('card_id')->values()->all();
            $newIds = DB::table('card')->where('card_sort_id', $newSortId)->orderBy('card_id')->pluck('card_id')->all();
            if (count($oldIds) !== count($newIds)) {
                throw new \Exception('card copy count mismatch');
            }
            $cardMap = array_combine($oldIds, $newIds);

            // 3. 複製抽卡預設及順序表（card_id 映射到新卡；因所有卡均已複製，僅指向已刪除卡的序位會被跳過）
            $presets = DB::table('draw_preset')->where('card_sort_id', $card_sort_id)->get();
            foreach ($presets as $p) {
                $pd = (array)$p;
                $oldPresetId = $pd['preset_id'];
                unset($pd['preset_id']);
                $pd['card_sort_id'] = $newSortId;
                $pd['created_at'] = $now;
                $pd['updated_at'] = $now;
                $newPresetId = DB::table('draw_preset')->insertGetId($pd);

                $items = DB::table('draw_preset_items')
                    ->where('preset_id', $oldPresetId)
                    ->orderBy('seq')
                    ->get(['seq', 'card_id']);
                $itemRows = [];
                foreach ($items as $it) {
                    if (!isset($cardMap[$it->card_id])) {
                        continue;
                    }
                    $itemRows[] = [
                        'preset_id' => $newPresetId,
                        'card_sort_id' => $newSortId,
                        'seq' => $it->seq,
                        'card_id' => $cardMap[$it->card_id],
                    ];
                }
                foreach (array_chunk($itemRows, 500) as $chunk) {
                    DB::table('draw_preset_items')->insert($chunk);
                }
            }

            // 4. 原卡池軟刪除；池內卡行保持原狀，歷史訂單/背包/統計均不受影響
            DB::table('card_sort')
                ->where('card_sort_id', $card_sort_id)
                ->update(['status' => -1, 'updated_at' => $now]);

            DB::commit();
            return $this->success(__('web.success'), [
                'card_sort_id' => $newSortId,
                'copied_cards' => count($cardMap),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('initCardSortWeb failed: '.$e->getMessage());
            return $this->error($e->getMessage() ?: __('web.network_error'));
        }
    }
}
