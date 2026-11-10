<?php
/*
 * File: DrawPresetController.php
 * Project: NewDemo
 * Created Time: 2026-05-22 08:51:37 am
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2026-09-10 12:00:44 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2026 成都睿創科技有限公司
 */


namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DrawPreset;
use App\Models\Card;
use App\Models\CardSort;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DrawPresetController extends Controller
{
    protected $Card;

    public function __construct()
    {
        $this->Card = new Card();
    }

    /**
     * 後台-獲取預設抽卡列表
     */
    public function getDrawPresetListWeb(Request $request)
    {
        $limit = $request->post('limit', 10);
        $page = $request->post('page', 1);
        $language = self::getLanguageWeb();

        $model = DrawPreset::join('card_sort', 'draw_preset.card_sort_id', '=', 'card_sort.card_sort_id')
            ->where('card_sort.status', '<>', -1) // 排除已初始化/刪除的卡池
            ->orderBy('draw_preset.created_at', 'desc');

        $count = (clone $model)->count();
        $offset = ($page - 1) * $limit;
        $list = $model->select('draw_preset.preset_id', 'draw_preset.card_sort_id',
                'draw_preset.is_active', 'draw_preset.is_lock',
                'draw_preset.created_at', 'draw_preset.updated_at',
                'card_sort.' . $language . 'card_sort_name as product_sort_name')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->toArray();

        // 為每筆預設附加卡片統計（total / undrawn / drawn）
        $sortIds = array_column($list, 'card_sort_id');
        if (!empty($sortIds)) {
            // 各卡包的卡片總數與 stock 統計
            $cardStats = DB::table('card')
                ->whereIn('card_sort_id', $sortIds)
                ->where('card_status', 1)
                ->groupBy('card_sort_id')
                ->selectRaw('card_sort_id,
                    count(*) as total_cards,
                    sum(case when card_stock > 0 then 1 else 0 end) as undrawn_base,
                    sum(case when card_stock < 1 then 1 else 0 end) as drawn_base')
                ->get()
                ->keyBy('card_sort_id');

            // pending draw_session 中的卡片
            $pendingCardIds = DB::table('draw_session')
                ->where('status', 0)
                ->where('expires_at', '>', now())
                ->whereIn('card_sort_id', $sortIds)
                ->get(['draw_data'])
                ->flatMap(function ($session) {
                    $cards = json_decode($session->draw_data, true);
                    return $cards ? array_column($cards, 'card_id') : [];
                })
                ->filter()
                ->unique()
                ->values()
                ->toArray();

            $pendingCountBySort = [];
            if (!empty($pendingCardIds)) {
                $pendingCounts = DB::table('card')
                    ->whereIn('card_id', $pendingCardIds)
                    ->whereIn('card_sort_id', $sortIds)
                    ->groupBy('card_sort_id')
                    ->selectRaw('card_sort_id, count(*) as pending_count')
                    ->get()
                    ->keyBy('card_sort_id');
                foreach ($pendingCounts as $ps) {
                    $pendingCountBySort[(int)$ps->card_sort_id] = (int)$ps->pending_count;
                }
            }

            foreach ($list as &$item) {
                $sid = (int)$item['card_sort_id'];
                $stat = $cardStats[$sid] ?? null;
                $totalCards = $stat ? (int)$stat->total_cards : 0;
                $undrawnBase = $stat ? (int)$stat->undrawn_base : 0;
                $drawnBase = $stat ? (int)$stat->drawn_base : 0;
                $pendingCnt = $pendingCountBySort[$sid] ?? 0;

                $item['total_card_count'] = $totalCards;
                $item['undrawn_card_count'] = max(0, $undrawnBase - $pendingCnt);
                $item['drawn_card_count'] = $drawnBase + $pendingCnt;
            }
            unset($item);
        }

        $results = [
            'count' => $count,
            'data' => $list
        ];
        return $this->success(__('web.success'), $results);
    }

    /**
     * 後台-獲取指定卡包的卡片列表（合併預設排序數據）
     */
    public function getPresetCardsWeb(Request $request)
    {
        $productSortId = $request->post('product_sort_id', $request->post('card_sort_id'));
        if (empty($productSortId)) {
            return $this->error(__('web.param_error'));
        }
        $language = self::getLanguageWeb();

        $cards = $this->Card->join('card_sort', 'card.card_sort_id', '=', 'card_sort.card_sort_id')
            ->where('card.card_sort_id', $productSortId)
            ->where('card.card_status', 1)
            ->select('card.card_id', 'card.card_name', 'card.en_card_name',
                'card.card_img', 'card.card_price', 'card.card_cost',
                'card.card_sort_id', 'card.card_stock', 'card.card_tag',
                'card_sort.' . $language . 'card_sort_name as card_sort_name')
            ->orderBy('card.created_at', 'desc')
            ->get()
            ->toArray();

        // 查詢 pending draw_session 中的卡片，將暫佔中的卡片標記為已抽取
        $pendingCardIds = DB::table('draw_session')
            ->where('status', 0)
            ->where('expires_at', '>', now())
            ->where('card_sort_id', $productSortId)
            ->get(['draw_data'])
            ->flatMap(function ($session) {
                $cards = json_decode($session->draw_data, true);
                return $cards ? array_column($cards, 'card_id') : [];
            })
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        if (!empty($pendingCardIds)) {
            foreach ($cards as &$card) {
                if (in_array($card['card_id'], $pendingCardIds)) {
                    $card['card_stock'] = 0;
                }
            }
            unset($card);
        }

        $preset = DrawPreset::where('card_sort_id', $productSortId)
            ->where('is_active', 1)
            ->first();

        $presetData = $preset ? $preset->preset_data : null;
        $isLock = $preset ? $preset->is_lock : 0;

        return $this->success(__('web.success'), [
            'cards' => $cards,
            'preset_data' => $presetData,
            'preset_id' => $preset ? $preset->preset_id : null,
            'is_lock' => $isLock
        ]);
    }

    /**
     * 後台-保存預設抽卡排序
     */
    public function saveDrawPresetWeb(Request $request)
    {
        $productSortId = $request->post('product_sort_id', $request->post('card_sort_id'));
        $presetData = $request->post('preset_data');

        if (empty($productSortId) || empty($presetData)) {
            return $this->error(__('web.param_error'));
        }

        $preset = DrawPreset::where('card_sort_id', $productSortId)->first();
        if ($preset) {
            $preset->preset_data = $presetData;
            $preset->is_active = 1;
            $preset->is_lock = 1;
            $preset->save();
        } else {
            DrawPreset::create([
                'card_sort_id' => $productSortId,
                'preset_data' => $presetData,
                'is_active' => 1,
                'is_lock' => 1
            ]);
        }

        // 重建抽卡用的預設順序表，讓新排序立即生效
        $this->Card->rebuildPresetItems((int)$productSortId);

        return $this->success(__('web.success'));
    }

    /**
     * 後台-隨機打亂並返回新的排序
     *
     * 功能說明：
     * 當管理員在 DrawPreset 編輯對話框中點擊「隨機排序」按鈕時，
     * 此接口會將指定卡包下的所有啟用卡片進行隨機打亂，
     * 並將打亂後的結果返回給前端供管理員預覽。
     * 注意：此接口僅返回打亂結果供預覽，並不會自動儲存到資料庫，
     * 管理員需確認後再呼叫 saveDrawPresetWeb 進行保存。
     *
     * 觸發時機：
     * - DrawPreset 編輯對話框中的「隨機排序」按鈕
     *
     */
    public function shuffleDrawPresetWeb(Request $request)
    {
        $productSortId = $request->post('product_sort_id', $request->post('card_sort_id'));
        if (empty($productSortId)) {
            return $this->error(__('web.param_error'));
        }
        $language = self::getLanguageWeb();
        $cards = $this->Card->join('card_sort', 'card.card_sort_id', '=', 'card_sort.card_sort_id')
            ->where('card.card_sort_id', $productSortId)
            ->where('card.card_status', 1)
            ->select('card.card_id', 'card.card_name', 'card.en_card_name',
                'card.card_img', 'card.card_price', 'card.card_cost',
                'card.card_sort_id', 'card.card_stock', 'card.card_tag',
                'card_sort.' . $language . 'card_sort_name as card_sort_name')
            ->orderBy('card.created_at', 'desc')
            ->get()
            ->toArray();

        // 查詢 pending draw_session 中的卡片，將暫佔中的卡片標記為已抽取
        $pendingCardIds = DB::table('draw_session')
            ->where('status', 0)
            ->where('expires_at', '>', now())
            ->where('card_sort_id', $productSortId)
            ->get(['draw_data'])
            ->flatMap(function ($session) {
                $cards = json_decode($session->draw_data, true);
                return $cards ? array_column($cards, 'card_id') : [];
            })
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        if (!empty($pendingCardIds)) {
            foreach ($cards as &$card) {
                if (in_array($card['card_id'], $pendingCardIds)) {
                    $card['card_stock'] = 0;
                }
            }
            unset($card);
        }

        shuffle($cards);
        return $this->success(__('web.success'), [
            'shuffled_cards' => $cards
        ]);
    }

    /**
     * 後台-自動為有卡片但無預設的卡包建立預設排序
     */
    public function autoCreatePresetsWeb()
    {
        $language = self::getLanguageWeb();

        $sortsWithCards = DB::table('card')
            ->join('card_sort', 'card.card_sort_id', '=', 'card_sort.card_sort_id')
            ->where('card.card_status', 1)
            ->where('card_sort.status', 1)
            ->select('card_sort.card_sort_id', 'card_sort.' . $language . 'card_sort_name as card_sort_name')
            ->distinct()
            ->get()
            ->toArray();

        $created = 0;
        foreach ($sortsWithCards as $sort) {
            $preset = DrawPreset::where('card_sort_id', $sort->card_sort_id)->first();
            if (!$preset) {
                $presetData = $this->buildPresetDataForSort($sort);
                if ($presetData === null) {
                    continue;
                }
                DrawPreset::create([
                    'card_sort_id' => $sort->card_sort_id,
                    'preset_data'     => $presetData,
                    'is_active'       => 1
                ]);
                $created++;
            } else {
                $presetData = $preset->preset_data;
                // 如果 preset_data 為空或非陣列（如 JSON 解碼失敗），直接重新生成
                if (empty($presetData) || !is_array($presetData)) {
                    $newPresetData = $this->buildPresetDataForSort($sort);
                    if ($newPresetData === null) {
                        continue;
                    }
                    $preset->preset_data = $newPresetData;
                    $preset->save();
                    $created++;
                    continue;
                }
                $existingIds = [];
                $hasOldFormat = false;
                foreach ($presetData as $pc) {
                    if (isset($pc['product_id']) && !isset($pc['card_id'])) {
                        $hasOldFormat = true;
                    }
                    $id = isset($pc['card_id']) ? $pc['card_id'] : (isset($pc['product_id']) ? $pc['product_id'] : null);
                    if ($id !== null) {
                        $existingIds[] = $id;
                    }
                }
                $currentCards = $this->Card->where('card.card_sort_id', $sort->card_sort_id)
                    ->where('card.card_status', 1)
                    ->select('card.card_id')
                    ->orderBy('card.created_at', 'desc')
                    ->get()
                    ->toArray();
                $hasNew = false;
                foreach ($currentCards as $cc) {
                    if (!in_array($cc['card_id'], $existingIds)) {
                        $hasNew = true;
                        break;
                    }
                }
                if ($hasNew || $hasOldFormat) {
                    $newPresetData = $this->buildPresetDataForSort($sort);
                    if ($newPresetData === null) {
                        continue;
                    }
                    $preset->preset_data = $newPresetData;
                    $preset->save();
                    $created++;
                }
            }
        }
        foreach ($sortsWithCards as $sort) {
            Cache::forget('draw:preset:' . $sort->card_sort_id);
        }

        return $this->success(__('web.success'), [
            'created' => $created,
            'total'   => count($sortsWithCards)
        ]);
    }

    /**
     * 為指定卡包建立預設排序資料（未抽卡在前，已抽卡在後）
     */
    private function buildPresetDataForSort($sort)
    {
        $cards = $this->Card->where('card.card_sort_id', $sort->card_sort_id)
            ->where('card.card_status', 1)
            ->select('card.card_id', 'card.card_name', 'card.en_card_name',
                'card.card_img', 'card.card_price', 'card.card_cost',
                'card.card_sort_id', 'card.card_stock', 'card.card_tag')
            ->orderBy('card.created_at', 'desc')
            ->get()
            ->toArray();
        if (empty($cards)) {
            return null;
        }
        $presetData = [];
        foreach ($cards as $card) {
            $presetData[] = [
                'card_id'      => $card['card_id'],
                'card_name'    => $card['card_name'],
                'en_card_name' => $card['en_card_name'] ?? '',
                'card_img'     => $card['card_img'] ?? '',
                'card_price'   => $card['card_price'] ?? 0,
                'card_cost'    => $card['card_cost'] ?? 0,
                'card_sort_id' => $card['card_sort_id'],
                'card_stock'   => (int)($card['card_stock'] ?? 1),
                'card_tag'     => $card['card_tag'] ?? '',
            ];
        }
        $drawnCards = [];
        $undrawnCards = [];
        foreach ($presetData as $pc) {
            $stock = $pc['card_stock'] ?? 0;
            if ($stock <= 0) {
                $drawnCards[] = $pc;
            } else {
                $undrawnCards[] = $pc;
            }
        }
        shuffle($drawnCards);
        shuffle($undrawnCards);
        return array_merge($undrawnCards, $drawnCards);
    }

    /**
     * 後台-刪除預設抽卡排序
     */
    public function deleteDrawPresetWeb(Request $request)
    {
        $presetId = $request->post('preset_id');
        if (empty($presetId)) {
            return $this->error(__('web.param_error'));
        }

        $preset = DrawPreset::where('preset_id', $presetId)->first();
        if (!empty($preset)) {
            // 清除抽卡用的預設順序快取
            Cache::forget('draw:preset:' . $preset->card_sort_id);
        }
        DrawPreset::where('preset_id', $presetId)->delete();

        return $this->success(__('web.success'));
    }

    /**
     * 後台-解鎖預設抽卡排序（允許重新編輯排序）
     */
    public function unlockDrawPresetWeb(Request $request)
    {
        $presetId = $request->post('preset_id');
        if (empty($presetId)) {
            return $this->error(__('web.param_error'));
        }

        DrawPreset::where('preset_id', $presetId)->update(['is_lock' => 0]);

        return $this->success(__('web.success'));
    }
}
