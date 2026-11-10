<?php
/*
 * File: CardSort.php
 * Project: NewDemo
 * Created Time: 2026-06-04 04:11:11 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2026-09-10 4:23:01 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2026 成都睿創科技有限公司
 */



namespace App\Models;

use DateTimeInterface;
use App\Models\Base;
use App\Models\Card;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;


/**
 * App\Models\CardSort
 */
class CardSort extends Base
{
    use Notifiable;
    protected $table = 'card_sort';
    protected $primaryKey = 'card_sort_id';
    public $timestamps = true;


    /**
     * 为数组 / JSON 序列化准备日期。
     */
    protected function serializeDate(DateTimeInterface $date){
        return $date->format($this->dateFormat ?: 'Y-m-d H:i:s');
    }


    /**
     * 函數構造
     */
    public function __construct(){
        $this->language = app('global_language') == "en" ? "en_" : "";
        // 生產庫可能缺少英文介紹欄位，缺失時回退中文，避免查詢報錯
        $introduceField = $this->language.'card_sort_introduce';
        if ($this->language === 'en_' && !self::hasColumnCached('card_sort', 'en_card_sort_introduce')) {
            $introduceField = 'card_sort_introduce';
        }
        $this->AppDetail = ['card_sort_id', 'card_sort_url_single', 'card_sort_url_ten', 'card_sort_url_hundred', $this->language.'card_sort_name as card_sort_name', $introduceField.' as card_sort_introduce', 'card_sort_img', 'status', 'card_draw_price', 'created_at', 'updated_at'];
        // 優惠卡包標記（未跑遷移前不加，避免查詢報錯）
        if (self::hasColumnCached('card_sort', 'is_once_only')) {
            // 用整體賦值代替 $this->AppDetail[] = ...，避免對魔術屬性做間接修改報
            // "Indirect modification of overloaded property has no effect"
            $this->AppDetail = array_merge($this->AppDetail, ['is_once_only']);
        }
        // 每日抽取次數限制（未跑遷移前不加，避免查詢報錯）
        if (self::hasColumnCached('card_sort', 'daily_draw_limit')) {
            $this->AppDetail = array_merge($this->AppDetail, ['daily_draw_limit']);
        }
        //每个值加前缀
       $table=$this->table;
       $this->PrefixAppDetail = array_map(function($item) use ($table) { return $table.'.' . $item ;}, $this->AppDetail);

    }

/*********************************************************會員等級限制*********************************************************/
    /**
     * 規範化「多會員等級限制」入參/存量資料，輸出統一結構
     * 支援：JSON 字串、陣列、物件；元素可為 member_id 或 ['member_id'=>x,'draw_limit'=>y]
     * @param mixed $value
     * @return array<int, array{member_id:int, draw_limit:int}>
     */
    public static function normalizeMemberLevelLimits($value){
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($value)) {
            return [];
        }
        $normalized = [];
        foreach ($value as $item) {
            if (is_array($item)) {
                $memberId = (int)($item['member_id'] ?? $item['value'] ?? 0);
                $drawLimit = max(0, (int)($item['draw_limit'] ?? $item['limit'] ?? 0));
            } else {
                $memberId = (int)$item;
                $drawLimit = 0;
            }
            if ($memberId <= 0) {
                continue;
            }
            // 同一等級重複時以最後一次配置為準
            $normalized[$memberId] = ['member_id' => $memberId, 'draw_limit' => $drawLimit];
        }
        return array_values($normalized);
    }

    /**
     * 解析卡池的會員等級限制，兼容舊字段 member_level_limit
     * 新字段有配置時優先；否則舊字段 > 0 時視為「單等級、不額外限制」
     * @param mixed $jsonValue card_sort.member_level_limits
     * @param int $legacyLimit card_sort.member_level_limit
     * @return array<int, array{member_id:int, draw_limit:int}>
     */
    public static function resolveMemberLevelLimits($jsonValue, $legacyLimit = 0){
        $limits = self::normalizeMemberLevelLimits($jsonValue);
        if (!empty($limits)) {
            return $limits;
        }
        $legacyLimit = (int)$legacyLimit;
        if ($legacyLimit > 0) {
            return [['member_id' => $legacyLimit, 'draw_limit' => 0]];
        }
        return [];
    }

    /**
     * 按用戶當前檔位(member_end)匹配卡池允許的等級配置
     * @param array $limits resolveMemberLevelLimits 的結果
     * @param array $memberInfoMap member_id => ['name','member_start','member_end']
     * @param int $userMemberEnd 用戶當前檔位 member_end，0=未定級
     * @return array|null 匹配到的等級配置，未匹配回傳 null
     */
    public static function matchMemberLevelLimit(array $limits, array $memberInfoMap, $userMemberEnd){
        if (empty($limits)) {
            return null;
        }
        foreach ($limits as $level) {
            $info = $memberInfoMap[(int)$level['member_id']] ?? null;
            if ($info && $userMemberEnd > 0 && (int)$info['member_end'] === (int)$userMemberEnd) {
                return $level;
            }
        }
        return null;
    }

/*********************************************************关联**********************************************************************/
    /**
     * 關聯卡片表
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function product(){
        return $this->hasMany('App\Models\Card','card_sort_id','card_sort_id');
    }




/*********************************************************admin**********************************************************************/

    /**
     * 獲取分類列表
     * @param $data
     * @param $language
     * @return mixed
     */
    public function getCardSortListWeb($data,$language){
        $limit = $data['limit'];
        $page = ($data['page'] - 1) * $limit;
        $where[] = array('card_sort.status', '<>', -1);
        if(isset($data['status']) && (!empty($data['status']) || is_numeric($data['status']))){
            $where['card_sort.status'] = $data['status'];
        }
        if (!empty($data['card_sort_name'])) {
            $where[] = array('card_sort.'.$language.'card_sort_name', 'like', '%' . $data['card_sort_name'] . '%');
        }
        $datas['data'] = $this->from('card_sort')
            ->where($where)
            ->select('card_sort.*')
            ->selectRaw('(SELECT COUNT(*) FROM card WHERE card.card_sort_id = card_sort.card_sort_id AND card.card_status <> -1 AND card.card_stock = 0) as drawn_count')
            ->selectRaw('(SELECT COUNT(*) FROM card WHERE card.card_sort_id = card_sort.card_sort_id AND card.card_status <> -1 AND card.card_stock > 0) as undrawn_count')
            ->offset($page)->limit($limit)
            ->orderBy('card_sort.sort', 'asc')
            ->orderBy('card_sort.card_sort_id', 'desc')
            ->get()->toArray();
        // 品牌以陣列回給後台表單（DB 儲存為 JSON 字串）
        // has_locked_preset：該卡池是否已有「已鎖定且啟用」的抽卡預設（未鎖定不允許開啟抽卡入口）
        $lockedSortIds = $this->getLockedPresetSortIds(array_column($datas['data'], 'card_sort_id'));
        foreach ($datas['data'] as $key => $row) {
            $datas['data'][$key]['card_brands'] = $this->decodeBrands($row['card_brands'] ?? null);
            $datas['data'][$key]['has_locked_preset'] = in_array((int)$row['card_sort_id'], $lockedSortIds, true) ? 1 : 0;
            // 多會員等級限制回給後台表單/列表（DB 儲存為 JSON 字串）
            $datas['data'][$key]['member_level_limits'] = self::resolveMemberLevelLimits(
                $row['member_level_limits'] ?? null,
                $row['member_level_limit'] ?? 0
            );
        }
        $datas['count'] = $this->from('card_sort')
            ->where($where)->count();
        return $datas;
    }

    /**
     * 添加/編輯卡片分類
     * @param $data
     * @return array
     */
    public function handleCardSortListWeb($data){
        $excludeId = empty($data['card_sort_id']) ? 0 : (int)$data['card_sort_id'];
        if(empty($data['card_sort_id'])){
            $data['created_at'] = self::getDate();
        }
        // 名稱重複校驗：僅針對未軟刪除（status <> -1）的其他卡池；中英文任一名稱相同即視為重複
        // 英文名為空時不參與匹配（避免兩個空英文名被誤判重複）
        $enName = trim((string)($data['en_card_sort_name'] ?? ''));
        $hasAttributeName = $this->where('status', '<>', -1)
            ->when($excludeId > 0, function ($query) use ($excludeId) {
                $query->where('card_sort_id', '<>', $excludeId);
            })
            ->where(function ($query) use ($data, $enName) {
                $query->where('card_sort_name', $data['card_sort_name']);
                if ($enName !== '') {
                    $query->orWhere('en_card_sort_name', $enName);
                }
            })
            ->first();
        if(!empty($hasAttributeName)){
            return $this->error(__('web.name_duplication'));
        }else{
            if(($data['status'] != 1) && !empty($data['card_sort_id'])){
                $hasProduct = Card::where('card_sort_id',$data['card_sort_id'])->first();
                if(!empty($hasProduct)){
                    return $this->error(__('web.existingProduct'));
                }
            }
            // 只保留 card_sort 資料表實際存在的欄位
            $allowed = ['card_sort_id','card_sort_name','en_card_sort_name','card_sort_img','card_sort_url_single','card_sort_url_ten','card_sort_url_hundred','card_sort_introduce','en_card_sort_introduce','card_draw_price','status','sort','created_at'];
            // 過濾數據表中不存在的欄位（生產庫可能缺少英文介紹欄位），避免編輯卡池報錯
            $allowed = array_values(array_filter($allowed, function ($column) {
                return $column === 'card_sort_id' || self::hasColumnCached('card_sort', $column);
            }));
            // 未跑遷移前不加該欄位，避免編輯卡池報錯
            if (self::hasColumnCached('card_sort', 'is_once_only')) {
                $allowed[] = 'is_once_only';
            }
            if (self::hasColumnCached('card_sort', 'draw_limit_per_user')) {
                $allowed[] = 'draw_limit_per_user';
            }
            if (self::hasColumnCached('card_sort', 'daily_draw_limit')) {
                $allowed[] = 'daily_draw_limit';
            }
            // 會員等級限制（存 user_member.member_id，0=不限制）
            if (self::hasColumnCached('card_sort', 'member_level_limit')) {
                $allowed[] = 'member_level_limit';
            }
            if (self::hasColumnCached('card_sort', 'member_level_limits')) {
                $allowed[] = 'member_level_limits';
            }
            $dbData = array_intersect_key($data, array_flip($allowed));
            if (self::hasColumnCached('card_sort', 'member_level_limit')) {
                $dbData['member_level_limit'] = max(0, (int)($dbData['member_level_limit'] ?? 0));
            }
            // 多會員等級限制 + 每等級可抽張數（JSON）：新字段有傳值時以新字段為準，舊字段清零避免重複生效
            if (self::hasColumnCached('card_sort', 'member_level_limits')) {
                $levelLimits = self::normalizeMemberLevelLimits($data['member_level_limits'] ?? null);
                $dbData['member_level_limits'] = empty($levelLimits)
                    ? null
                    : json_encode($levelLimits, JSON_UNESCAPED_UNICODE);
                if (array_key_exists('member_level_limits', $data) && self::hasColumnCached('card_sort', 'member_level_limit')) {
                    $dbData['member_level_limit'] = 0;
                }
            }
            // 卡池品牌（可多個）：入參為陣列或逗號/JSON 字串，統一存成 JSON
            if (array_key_exists('card_brands', $data) && self::hasColumnCached('card_sort', 'card_brands')) {
                $dbData['card_brands'] = json_encode($this->normalizeBrands($data['card_brands']), JSON_UNESCAPED_UNICODE);
            }
            // 新建卡池默認關閉抽卡入口（需先在「抽卡預設」鎖定預設，再手動開啟）
            if (empty($data['card_sort_id']) && self::hasColumnCached('card_sort', 'draw_enable')) {
                $dbData['draw_enable'] = 0;
            }
            $res = $this->updateOrInsert(['card_sort_id' => ($data['card_sort_id'] ?? null)], $dbData);
            if($res){
                return $this->success(__('web.success'));
            }else{
                return $this->error(__('web.network_error'));
            }
        }
    }

    /**
     * 刪除卡片分類（同步軟刪除該卡包下的所有卡片）
     * @param $CardSortID
     * @return array
     */
    public function deleteCardSortListWeb($CardSortID){
        DB::beginTransaction();
        try {
            // 軟刪除卡包下的所有卡片
            Card::where('card_sort_id', $CardSortID)->where('card_status', 1)->update(['card_status' => -1]);
            // 軟刪除卡包
            $this->where('card_sort_id', $CardSortID)->update(['status' => -1]);
            DB::commit();
            return $this->success(__('web.delete_success'));
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error(__('web.delete_fail'));
        }
    }

    /**
     * 後台-開啟/關閉單個卡池的抽卡入口
     * @param $data
     * @return array
     */
    public function editCardSortDrawEnableWeb($data){
        $cardSortId = (int)($data['card_sort_id'] ?? 0);
        if ($cardSortId <= 0 || !isset($data['draw_enable'])) {
            return $this->error(__('api.param_error'));
        }
        $exists = $this->where('card_sort_id', $cardSortId)->where('status', '<>', -1)->exists();
        if (!$exists) {
            return $this->error(__('api.param_error'));
        }
        $enable = ((int)$data['draw_enable'] === 1) ? 1 : 0;
        // 開啟抽卡入口前必須先鎖定該卡池的抽卡預設，避免未定好卡序就對外開放
        if ($enable === 1 && !$this->hasLockedPreset($cardSortId)) {
            return $this->error(__('web.draw_need_locked_preset'));
        }
        $this->where('card_sort_id', $cardSortId)->update([
            'draw_enable' => $enable,
            'updated_at'  => self::getDate(),
        ]);
        return $this->success(__('web.success'), ['card_sort_id' => $cardSortId, 'draw_enable' => $enable]);
    }

    /**
     * 後台-開啟/關閉單個卡池的「優惠卡包」標記
     * 開啟後該卡池每個帳號僅能抽一次，且 App 端只顯示單抽按鈕
     * @param $data
     * @return array
     */
    public function editCardSortOnceOnlyWeb($data){
        $cardSortId = (int)($data['card_sort_id'] ?? 0);
        if ($cardSortId <= 0 || !isset($data['is_once_only'])) {
            return $this->error(__('api.param_error'));
        }
        if (!self::hasColumnCached('card_sort', 'is_once_only')) {
            return $this->error(__('web.fail'));
        }
        $exists = $this->where('card_sort_id', $cardSortId)->where('status', '<>', -1)->exists();
        if (!$exists) {
            return $this->error(__('api.param_error'));
        }
        $onceOnly = ((int)$data['is_once_only'] === 1) ? 1 : 0;
        $this->where('card_sort_id', $cardSortId)->update([
            'is_once_only' => $onceOnly,
            'updated_at'   => self::getDate(),
        ]);
        return $this->success(__('web.success'), ['card_sort_id' => $cardSortId, 'is_once_only' => $onceOnly]);
    }

    /**
     * 後台-一鍵開啟/關閉全部卡池的抽卡入口
     * @param $data
     * @return array
     */
    public function batchCardSortDrawEnableWeb($data){
        if (!isset($data['draw_enable'])) {
            return $this->error(__('api.param_error'));
        }
        $enable = ((int)$data['draw_enable'] === 1) ? 1 : 0;

        if ($enable === 1) {
            // 僅開啟「已鎖定且啟用抽卡預設」的卡池，其餘跳過並回報數量
            $lockedSortIds = $this->getLockedPresetSortIds();
            $affected = $this->where('status', '<>', -1)->whereIn('card_sort_id', $lockedSortIds)->update([
                'draw_enable' => 1,
                'updated_at'  => self::getDate(),
            ]);
            $skipped = $this->where('status', '<>', -1)->whereNotIn('card_sort_id', $lockedSortIds)->count();
            return $this->success(__('web.success'), ['draw_enable' => 1, 'affected' => $affected, 'skipped' => $skipped]);
        }

        $affected = $this->where('status', '<>', -1)->update([
            'draw_enable' => 0,
            'updated_at'  => self::getDate(),
        ]);
        return $this->success(__('web.success'), ['draw_enable' => 0, 'affected' => $affected, 'skipped' => 0]);
    }

    /**
     * 取「已鎖定且啟用」抽卡預設的卡池ID集合
     * @param array $cardSortIds 為空時取全部
     * @return array
     */
    private function getLockedPresetSortIds($cardSortIds = []){
        if (!self::hasTableCached('draw_preset')) {
            return [];
        }
        $query = DB::table('draw_preset')->where('is_active', 1)->where('is_lock', 1);
        if (!empty($cardSortIds)) {
            $query->whereIn('card_sort_id', array_map('intval', $cardSortIds));
        }
        return array_map('intval', $query->distinct()->pluck('card_sort_id')->all());
    }

    /**
     * 該卡池是否已有「已鎖定且啟用」的抽卡預設
     * @param int $cardSortId
     * @return bool
     */
    private function hasLockedPreset($cardSortId){
        return in_array((int)$cardSortId, $this->getLockedPresetSortIds([$cardSortId]), true);
    }

    /**
     * 計算卡池抽卡價格統計（正價口徑）
     * 公式：抽卡價格（取整）= 卡牌總價 ÷ 卡牌數量 × 百分比 ÷ 100
     * 正價：優先取卡庫 spider_cards.deal_price（與卡庫展示一致的即時正價），
     *       無該資料（未關聯來源或正價為 0）時用入池快照 card.card_cost
     * @param int $cardSortId
     * @return array {count,total_price,percent,auto_update_price,card_draw_price,computed_draw_price}
     */
    public static function poolDrawPriceStats($cardSortId){
        $cardSortId = (int)$cardSortId;
        $row = DB::table('card')
            ->leftJoin('spider_cards', 'card.trading_card_id', '=', 'spider_cards.id')
            ->where('card.card_sort_id', $cardSortId)
            ->where('card.card_status', '<>', -1)
            ->selectRaw('COUNT(*) AS card_count, COALESCE(SUM(COALESCE(NULLIF(spider_cards.deal_price, 0), card.card_cost, 0)), 0) AS total_price')
            ->first();
        $count = (int)($row->card_count ?? 0);
        $total = round((float)($row->total_price ?? 0), 2);

        $percent = 100.0;
        $autoUpdate = 1;
        $drawPrice = 0.0;
        try {
            if (self::hasColumnCached('card_sort', 'draw_price_percent')) {
                $sort = DB::table('card_sort')->where('card_sort_id', $cardSortId)
                    ->first(['card_draw_price', 'draw_price_percent', 'auto_update_price']);
                if ($sort) {
                    $percent = $sort->draw_price_percent !== null ? (float)$sort->draw_price_percent : 100.0;
                    $autoUpdate = $sort->auto_update_price !== null ? (int)$sort->auto_update_price : 1;
                    $drawPrice = (float)$sort->card_draw_price;
                }
            }
        } catch (\Throwable $e) {
            // 欄位未遷移時使用預設值
        }

        // 讀取每日自動更新的時間點（app_settings 表的 auto_update_time1/time2）
        $updateTime1 = '05:00';
        $updateTime2 = '18:00';
        try {
            $settings = DB::table('app_settings')
                ->whereIn('key', ['auto_update_time1', 'auto_update_time2'])
                ->pluck('value', 'key');
            if (isset($settings['auto_update_time1']) && $settings['auto_update_time1'] !== '') {
                $updateTime1 = $settings['auto_update_time1'];
            }
            if (isset($settings['auto_update_time2']) && $settings['auto_update_time2'] !== '') {
                $updateTime2 = $settings['auto_update_time2'];
            }
        } catch (\Throwable $e) {
            // 表不存在或欄位異常時使用預設值
        }

        $computed = $count > 0 ? (int)round($total / $count * ($percent / 100.0)) : 0;
        return [
            'count'               => $count,
            'total_price'         => $total,
            'percent'             => $percent,
            'auto_update_price'   => $autoUpdate,
            'card_draw_price'     => $drawPrice,
            'computed_draw_price' => $computed,
            'auto_update_time1'   => $updateTime1,
            'auto_update_time2'   => $updateTime2,
        ];
    }

    /**
     * 按公式重算並寫回卡池抽卡價格
     * @param int $cardSortId
     * @param bool $force true=手動保存/指令強制更新；false=僅在卡池 auto_update_price=1 時更新
     * @return array|null 統計數據；被開關跳過時返回 null
     */
    public static function recalcAndSaveDrawPrice($cardSortId, $force = false){
        $cardSortId = (int)$cardSortId;
        $stats = self::poolDrawPriceStats($cardSortId);
        if (!$force && (int)$stats['auto_update_price'] !== 1) {
            return null;
        }
        $update = ['card_draw_price' => $stats['computed_draw_price']];
        if (self::hasColumnCached('card_sort', 'draw_price_updated_at')) {
            $update['draw_price_updated_at'] = self::getDate();
        }
        DB::table('card_sort')->where('card_sort_id', $cardSortId)->update($update);
        $stats['card_draw_price'] = (float)$stats['computed_draw_price'];
        return $stats;
    }

    /**
     * 後台保存卡池抽卡價格設置（手動百分比 / 是否自動更新），保存後立即按公式重算抽卡價格
     * @param array $data
     * @return array
     */
    public function updatePoolDrawPriceSetting($data){
        $cardSortId = (int)($data['card_sort_id'] ?? 0);
        if ($cardSortId <= 0) {
            return $this->error(__('api.param_error'));
        }
        $exists = $this->where('card_sort_id', $cardSortId)->where('status', '<>', -1)->exists();
        if (!$exists) {
            return $this->error(__('api.param_error'));
        }
        $update = [];
        if (array_key_exists('draw_price_percent', $data) && self::hasColumnCached('card_sort', 'draw_price_percent')) {
            $percent = (float)$data['draw_price_percent'];
            if ($percent < 0 || $percent > 9999) {
                return $this->error(__('api.param_error'));
            }
            $update['draw_price_percent'] = $percent;
        }
        if (array_key_exists('auto_update_price', $data) && self::hasColumnCached('card_sort', 'auto_update_price')) {
            $update['auto_update_price'] = (int)$data['auto_update_price'] === 1 ? 1 : 0;
        }
        if (!empty($update)) {
            $update['updated_at'] = self::getDate();
            $this->where('card_sort_id', $cardSortId)->update($update);
        }
        // 手動保存一律立即重算並寫回實際抽卡價格
        $stats = self::recalcAndSaveDrawPrice($cardSortId, true);
        return $this->success(__('web.success'), array_merge(['card_sort_id' => $cardSortId], $stats ?: []));
    }

    /**
     * 定時任務：重算所有「開啟自動更新」卡池的抽卡價格
     * 以 draw_price_updated_at 做分鐘級冪等，避免定時調度重入或兩個時間點相同時重複更新
     * @return array {updated,skipped,items}
     */
    public static function autoUpdateAllPoolDrawPrices(){
        $updated = 0;
        $skipped = 0;
        $items = [];
        if (!self::hasColumnCached('card_sort', 'auto_update_price')) {
            return ['updated' => 0, 'skipped' => 0, 'items' => []];
        }
        $ids = DB::table('card_sort')
            ->where('status', '<>', -1)
            ->where('auto_update_price', 1)
            ->pluck('card_sort_id')
            ->map(function ($v) { return (int)$v; })
            ->toArray();
        foreach ($ids as $id) {
            $last = null;
            if (self::hasColumnCached('card_sort', 'draw_price_updated_at')) {
                $last = DB::table('card_sort')->where('card_sort_id', $id)->value('draw_price_updated_at');
            }
            if ($last && strtotime($last) !== false && abs(time() - strtotime($last)) < 120) {
                $skipped++;
                continue;
            }
            $stats = self::recalcAndSaveDrawPrice($id, false);
            if ($stats !== null) {
                $updated++;
                $items[] = ['card_sort_id' => $id, 'draw_price' => $stats['computed_draw_price']];
            } else {
                $skipped++;
            }
        }
        return ['updated' => $updated, 'skipped' => $skipped, 'items' => $items];
    }

    /**
     * 獲取所有分類列表
     * @param $language
     * @return array
     */
    public function getAllCardSortWeb($language){
        $data = $this->where('status',1)->select('card_sort_id','sort',$language.'card_sort_name as card_sort_name')->orderBy('sort','asc')->orderBy('card_sort_id','desc')->get()->toArray();
        return $this->success(__('web.success'),$data);
    }

    /**
     * 獲取所有分類商品
     * @param $data
     * @param $language
     * @return array
     */
    public function getAllSortProductWeb($data,$language){
        $limit = $data['limit'];
        $page = ($data['page'] - 1) * $limit;
        $where[] = array('product_status', '=', 1);
        if (!empty($data['product_name'])) {
            $where[] = array($language.'product_name', 'like', '%' . $data['product_name'] . '%');
        }
        if (!empty($data['product_type'])) {
            $where['product_type'] =  $data['product_type'];
        }
        if (!empty($data['card_sort_id'])) {
            $where['card_sort_id'] =  $data['card_sort_id'];
        }

        $datas['data'] = Product::with(['productRelation','productSpecsKey'=>function ($query){
            $query->with('productSpecsValue');
        }])->where($where)
            ->offset($page)->limit($limit)
            ->select('product_id','card_sort_id',$language.'product_name as product_name','product_img','is_specs','product_type','product_price',
                'product_cost','product_stock')
            ->get()->toArray();

        $datas['count'] = Product::where($where)
            ->offset($page)->limit($limit)
            ->select('product_id','card_sort_id',$language.'product_name as product_name','product_img','is_specs','product_type','product_price',
                'product_cost','product_stock')
            ->count();
        if(!empty($datas['data'])){
            $shop = ShopSetting::where('shop_id',1)->first();
            for($i=0;$i<count($datas['data']);$i++){
                $datas['data'][$i]['is_tostore'] = $shop['is_tostore'];
                $datas['data'][$i]['tostore_price'] = $shop['tostore_price'];
                if(!empty($datas['data'][$i]['product_relation'])){
                    for($k=0;$k<count($datas['data'][$i]['product_relation']);$k++){
                        $datas['data'][$i]['product_relation'][$k]['product_specs_value'] = [];
                        $datas['data'][$i]['product_relation'][$k]['product_specs_value'] = ProductSpecsValue::whereIn('product_specs_value_id',explode('-',$datas['data'][$i]['product_relation'][$k]['product_specs_value_id']))->get()->toArray();
                    }
                }
            }
        }

        return $this->success(__('web.success'),$datas);
    }



/**********************************************************api**********************************************************************/





    /**
     * 根據服務或者卡片獲得卡片分类列表
     * @param $data
     * @return mixed
     */
    public function getCardSort($data){
        $limit = empty($data['limit']) ? 10 : $data['limit'];
        $page =  ( $data['page'] - 1 ) * $limit;
        $where[] = array('status', '<>', -1);
        // 關閉抽卡入口的卡池不展示（對應後台「卡池管理」的抽卡開關）
        $where[] = array('draw_enable', '<>', 0);
        // 依標籤篩選卡池：card_tag_order 為後台在該卡池標記的標籤 JSON 陣列
        if (!empty($data['tag'])) {
            $where[] = array('card_tag_order', 'like', $this->jsonArrayLikePatternEscaped($data['tag']));
        }
        $effects=array();
        $info = $this->where($where)->select($this->PrefixAppDetail)
                     ->orderBy('sort','asc')
                     ->orderBy('card_sort_id','desc')->distinct('card_sort_id')->paginate($limit , $page);
        $effects=$info->items();
        $AllCount = $this->where($where)->count();
        $results['AllCount']=$AllCount;
        $results['count']=$info->count();
        $results['currentPage']=$info->currentPage();
        $results['limit']=$info->perPage();
        $results['lastPage']=$info->lastPage();
        $results['data']=$effects;
        if(!empty($effects)){
            $sortIds = array_column($effects, 'card_sort_id');
            $stockSums = DB::table('card')
                ->whereIn('card_sort_id', $sortIds)
                ->where('card_status', '<>', -1)
                ->groupBy('card_sort_id')
                ->selectRaw('card_sort_id, sum(card_stock) as total_stock, count(*) as total_cards')
                ->get()
                ->keyBy('card_sort_id');

            $collectedIds = [];
            if(!empty($data['user_id'])){
                $collectedIds = DB::table('card_collect')
                    ->where('user_id', $data['user_id'])
                    ->whereIn('card_sort_id', $sortIds)
                    ->pluck('card_sort_id')
                    ->toArray();
            }
            $collectedIds = array_map('intval', $collectedIds);

            // 當前用戶已抽過的優惠卡池（用於 App 顯示「已抽取」/隱藏抽卡按鈕）
            $promoDrawnIds = [];
            if (!empty($data['user_id']) && self::hasTableCached('user_promo_draw')) {
                $promoDrawnIds = DB::table('user_promo_draw')
                    ->where('user_id', $data['user_id'])
                    ->whereIn('card_sort_id', $sortIds)
                    ->pluck('card_sort_id')
                    ->map(function ($v) { return (int)$v; })
                    ->toArray();
            }

            // 當前用戶的各卡池已抽次數（用於顯示剩餘次數）
            $userDrawCounts = [];
            if (!empty($data['user_id']) && self::hasTableCached('user_draw_count')) {
                $userDrawCounts = DB::table('user_draw_count')
                    ->where('user_id', $data['user_id'])
                    ->whereIn('card_sort_id', $sortIds)
                    ->pluck('draw_count', 'card_sort_id')
                    ->map(function ($v) { return (int)$v; })
                    ->toArray();
            }

            // 每個卡池的 draw_limit_per_user（需要從 card_sort 讀出，因為 select($this->PrefixAppDetail) 可能未包含）
            $drawLimits = [];
            if (self::hasColumnCached('card_sort', 'draw_limit_per_user')) {
                $drawLimits = DB::table('card_sort')
                    ->whereIn('card_sort_id', $sortIds)
                    ->pluck('draw_limit_per_user', 'card_sort_id')
                    ->map(function ($v) { return (int)($v ?? 0); })
                    ->toArray();
            }

            // 每日抽取次數限制 + 當前用戶今日已抽次數（每日0點隨日期切換自然重置）
            $dailyLimits = [];
            $todayDrawCounts = [];
            if (self::hasColumnCached('card_sort', 'daily_draw_limit')) {
                $dailyLimits = DB::table('card_sort')
                    ->whereIn('card_sort_id', $sortIds)
                    ->pluck('daily_draw_limit', 'card_sort_id')
                    ->map(function ($v) { return (int)($v ?? 0); })
                    ->toArray();
            }
            if (!empty($data['user_id']) && self::hasTableCached('user_daily_draw_count')) {
                $todayDrawCounts = DB::table('user_daily_draw_count')
                    ->where('user_id', $data['user_id'])
                    ->whereIn('card_sort_id', $sortIds)
                    ->whereDate('draw_date', date('Y-m-d'))
                    ->pluck('draw_count', 'card_sort_id')
                    ->map(function ($v) { return (int)$v; })
                    ->toArray();
            }

            // 會員等級限制（多等級 + 每等級可抽張數，兼容舊單等級字段）+ 當前用戶是否達標
            $memberLevelLimits = [];
            $memberLimits = [];
            $userMemberEnd = 0;
            $memberInfoMap = [];
            $hasLevelMulti = false;
            $hasLevelLegacy = false;
            try {
                $hasLevelMulti = self::hasColumnCached('card_sort', 'member_level_limits');
                $hasLevelLegacy = self::hasColumnCached('card_sort', 'member_level_limit');
            } catch (\Throwable $e) {
                $hasLevelMulti = false;
                $hasLevelLegacy = false;
            }
            if ($hasLevelMulti || $hasLevelLegacy) {
                $levelCols = ['card_sort_id'];
                if ($hasLevelMulti) {
                    $levelCols[] = 'member_level_limits';
                }
                if ($hasLevelLegacy) {
                    $levelCols[] = 'member_level_limit';
                }
                $levelRows = DB::table('card_sort')
                    ->whereIn('card_sort_id', $sortIds)
                    ->select($levelCols)
                    ->get();
                $levelIds = [];
                foreach ($levelRows as $levelRow) {
                    $limits = self::resolveMemberLevelLimits(
                        $hasLevelMulti ? ($levelRow->member_level_limits ?? null) : null,
                        $hasLevelLegacy ? ($levelRow->member_level_limit ?? 0) : 0
                    );
                    if (!empty($limits)) {
                        $memberLevelLimits[(int)$levelRow->card_sort_id] = $limits;
                        foreach ($limits as $levelItem) {
                            $levelIds[] = (int)$levelItem['member_id'];
                            $memberLimits[(int)$levelRow->card_sort_id] = (int)$levelItem['member_id'];
                        }
                    }
                }
                $levelIds = array_values(array_unique(array_filter($levelIds)));
                if (!empty($levelIds)) {
                    $memberInfoMap = UserMember::memberInfoByIds($levelIds, $this->language);
                }
                if (!empty($data['user_id']) && !empty($levelIds)) {
                    $userIntegral = (int)DB::table('users')->where('user_id', $data['user_id'])->value('user_integral_all');
                    $userMemberEnd = UserMember::memberEndByScore($userIntegral);
                }
            }

            foreach($results['data'] as $k => $v){
                $sid = $v['card_sort_id'];
                $stockRow = $stockSums[$sid] ?? null;
                $results['data'][$k]['card_inventory'] = $stockRow ? (int)$stockRow->total_stock : 0;
                $results['data'][$k]['undrawn_count'] = $stockRow ? (int)$stockRow->total_stock : 0;
                $results['data'][$k]['total_cards'] = $stockRow ? (int)$stockRow->total_cards : 0;
                $results['data'][$k]['drawn_count'] = $results['data'][$k]['total_cards'] - $results['data'][$k]['undrawn_count'];
                $results['data'][$k]['is_collected'] = (int)in_array((int)$v['card_sort_id'], $collectedIds);
                // 優惠卡包（每帳號僅能抽一次、僅單抽）與該用戶是否已抽過
                $once = isset($v['is_once_only']) ? (int)$v['is_once_only'] : 0;
                $results['data'][$k]['is_once_only'] = $once;
                // 每用戶抽取次數限制 + 剩餘次數（0 = 無限制）
                // draw_limit_per_user > 0 時以配置為準（user_draw_count）；否則優惠卡包看 user_promo_draw
                $limit = $drawLimits[$sid] ?? 0;
                $used  = $userDrawCounts[$sid] ?? 0;
                $promoUsed = in_array((int)$sid, $promoDrawnIds, true) ? 1 : 0;
                // 會員等級限制：多等級可分別配置可抽張數（0 = 該等級不額外限制）
                $levels = $memberLevelLimits[(int)$sid] ?? [];
                $levelDisplay = [];
                foreach ($levels as $levelItem) {
                    $info = $memberInfoMap[(int)$levelItem['member_id']] ?? null;
                    $levelDisplay[] = [
                        'member_id'     => (int)$levelItem['member_id'],
                        'name'          => $info ? $info['name'] : ('ID:' . $levelItem['member_id']),
                        'score_range'   => $info ? [(int)$info['member_start'], (int)$info['member_end']] : null,
                        'member_end'    => $info ? (int)$info['member_end'] : 0,
                        'draw_limit'    => (int)$levelItem['draw_limit'],
                    ];
                }
                $levelCfg = empty($levels) ? null : self::matchMemberLevelLimit($levels, $memberInfoMap, $userMemberEnd);
                $levelOk = empty($levels) ? 1 : ($levelCfg ? 1 : 0);
                $levelLimit = $levelCfg ? max(0, (int)$levelCfg['draw_limit']) : 0;
                // 實際生效上限 = 全局上限與等級上限取較小值（0 視為不限）
                $effectiveLimit = $limit;
                if ($levelLimit > 0) {
                    $effectiveLimit = $effectiveLimit > 0 ? min($effectiveLimit, $levelLimit) : $levelLimit;
                }
                $results['data'][$k]['draw_limit_per_user'] = $limit;
                $results['data'][$k]['member_level_limits'] = $levelDisplay;
                $results['data'][$k]['member_level_limit'] = !empty($levelDisplay) ? $levelDisplay[0]['member_id'] : 0;
                $results['data'][$k]['member_level_limit_name'] = !empty($levelDisplay)
                    ? implode('/', array_column($levelDisplay, 'name')) : null;
                $results['data'][$k]['member_level_limit_score_range'] = count($levelDisplay) === 1
                    ? $levelDisplay[0]['score_range'] : null;
                $results['data'][$k]['member_level_ok'] = $levelOk;
                $results['data'][$k]['member_level_draw_limit'] = $levelLimit;
                $results['data'][$k]['effective_draw_limit'] = $effectiveLimit;
                if (!$levelOk) {
                    $results['data'][$k]['draw_count']     = $used;
                    $results['data'][$k]['user_drawn']     = $used > 0 ? 1 : 0;
                    $results['data'][$k]['draw_remaining'] = 0;
                } elseif ($effectiveLimit > 0) {
                    $results['data'][$k]['draw_count']     = $used;
                    $results['data'][$k]['user_drawn']     = $used > 0 ? 1 : 0;
                    $results['data'][$k]['draw_remaining'] = max(0, $effectiveLimit - $used);
                } elseif ($once) {
                    $results['data'][$k]['draw_count']     = $promoUsed;
                    $results['data'][$k]['user_drawn']     = $promoUsed;
                    $results['data'][$k]['draw_remaining'] = 1 - $promoUsed;
                } else {
                    $results['data'][$k]['draw_count']     = 0;
                    $results['data'][$k]['user_drawn']     = 0;
                    $results['data'][$k]['draw_remaining'] = -1;
                }
                // 每日抽取次數限制 + 今日已抽/剩餘（0 = 不限制；與總次數限制同時生效）
                $dailyLimit = $dailyLimits[$sid] ?? 0;
                $results['data'][$k]['daily_draw_limit']    = $dailyLimit;
                $results['data'][$k]['draw_today']          = (int)($todayDrawCounts[$sid] ?? 0);
                $results['data'][$k]['draw_today_remaining'] = $dailyLimit > 0
                    ? max(0, $dailyLimit - (int)($todayDrawCounts[$sid] ?? 0))
                    : -1;
                // 本次單次可選的最大張數：卡池可抽庫存 / 每用戶剩餘 / 今日剩餘 / 優惠卡包 / 單次上限 取最小值
                $maxDrawCount = (int)$results['data'][$k]['card_inventory'];
                foreach (['draw_remaining', 'draw_today_remaining'] as $remainField) {
                    if ((int)$results['data'][$k][$remainField] > 0) {
                        $maxDrawCount = min($maxDrawCount, (int)$results['data'][$k][$remainField]);
                    }
                }
                if ($once) {
                    $maxDrawCount = min($maxDrawCount, 1);
                }
                $maxPerOrder = Card::maxDrawCountPerOrder();
                if ($maxPerOrder > 0) {
                    $maxDrawCount = min($maxDrawCount, $maxPerOrder);
                }
                if (!$levelOk) {
                    $maxDrawCount = 0;
                }
                $results['data'][$k]['max_draw_count'] = max(0, $maxDrawCount);
            }
            return $this->success( __('api.list_success'), $results);
        }else{
            return $this->success( __('api.list_null'), $results);
        }
    }

    /**
     * 構造「標籤存在於 card_tag_order」的 LIKE 匹配式
     * card_tag_order / card_brands 由後台以 json_encode 寫入（JSON 陣列字串），故用 json_encode($value)
     * 產生帶引號的字串（如 "SSR"、"\u4e00\u7b49\u734e"）再匹配；並轉義 LIKE 特殊字元 \ % _
     */
    private function jsonArrayLikePattern($value){
        // 存原生中文的欄位（如 card_brands）用這個
        // 存 Unicode 轉义的欄位（如 card_tag_order）見 jsonArrayLikePatternEscaped()
        return '%' . addcslashes(json_encode((string)$value, JSON_UNESCAPED_UNICODE), '\\%_') . '%';
    }

    /**
     * 同 jsonArrayLikePattern，但用預設 json_encode（Unicode 轉义中文）
     * 給 card_tag_order 用（該欄位歷史原因存 \uXXXX 轉义格式）
     */
    private function jsonArrayLikePatternEscaped($value){
        return '%' . addcslashes(json_encode((string)$value), '\\%_') . '%';
    }

    /**
     * App-獲取全部卡池標籤（去重）
     * 取所有可展示卡池（status=1 且 draw_enable<>0）的 card_tag_order，合併去重；
     * 「預設」為系統預設分組而非後台標記的標籤，不列入。
     * @return array
     */
    public function getCardTags(){
        $tags = [];

        // 同時從 card_tag_order 和 card_brands 取值，確保 APP 展示與篩選口徑一致
        $rows = $this->where('status', 1)
            ->where('draw_enable', '<>', 0)
            ->select(['card_tag_order', 'card_brands'])
            ->get();

        foreach ($rows as $row) {
            // card_tag_order（稀有度標籤）
            foreach (['card_tag_order', 'card_brands'] as $col) {
                $json = $row->$col ?? null;
                if (empty($json)) continue;
                $arr = json_decode($json, true);
                if (!is_array($arr)) continue;
                foreach ($arr as $tag) {
                    $tag = is_string($tag) ? trim($tag) : '';
                    if ($tag === '' || $tag === '預設' || $tag === '预设') continue;
                    $tags[$tag] = 1;
                }
            }
        }
        $tags = array_keys($tags);
        sort($tags);

        return $this->success(__('api.list_success'), $tags);
    }

    /**
     * App-獲取全部卡池品牌（去重）
     * 取所有可展示卡池（status=1 且 draw_enable<>0）的 card_brands 合併去重；
     * card_brands 由後台「卡池管理」手動維護，可存多個品牌。
     * @return array
     */
    public function getCardBrands(){
        // 遷移未執行時退回舊邏輯（按卡牌品牌），避免品牌欄為空
        if (!self::hasColumnCached('card_sort', 'card_brands')) {
            $brands = DB::table('card')
                ->whereNotNull('card_brand')->where('card_brand', '<>', '')
                ->where('card_status', '<>', -1)
                ->distinct()->pluck('card_brand')->toArray();
            sort($brands);
            return $this->success(__('api.list_success'), $brands);
        }

        $rows = $this->where('status', 1)
            ->where('draw_enable', '<>', 0)
            ->whereNotNull('card_brands')
            ->where('card_brands', '<>', '')
            ->pluck('card_brands')
            ->toArray();

        $brands = [];
        foreach ($rows as $json) {
            foreach ($this->decodeBrands($json) as $brand) {
                $brands[$brand] = 1;
            }
        }
        $brands = array_keys($brands);
        sort($brands);

        return $this->success(__('api.list_success'), $brands);
    }

    /**
     * 後台-獲取全部已維護過的卡池品牌（去重）
     * 與 App 端 getCardBrands 不同：不看 status/draw_enable，只要是未刪除卡池維護過的品牌都返回，
     * 供「卡池管理」新增/編輯表單的品牌下拉直接選取。
     * @return array
     */
    public function getAllCardBrands(){
        if (!self::hasColumnCached('card_sort', 'card_brands')) {
            return $this->success(__('web.success'), ['brands' => []]);
        }

        $rows = $this->where('status', '<>', -1)
            ->whereNotNull('card_brands')
            ->where('card_brands', '<>', '')
            ->pluck('card_brands')
            ->toArray();

        $brands = [];
        foreach ($rows as $json) {
            foreach ($this->decodeBrands($json) as $brand) {
                $brand = trim((string)$brand);
                if ($brand !== '') {
                    $brands[$brand] = 1;
                }
            }
        }
        $brands = array_keys($brands);
        sort($brands);

        return $this->success(__('web.success'), ['brands' => $brands]);
    }

    /**
     * 卡池品牌 JSON 字串 → 去重後的陣列
     * @param string|null $json
     * @return array
     */
    private function decodeBrands($json){
        if (empty($json)) {
            return [];
        }
        $arr = json_decode($json, true);
        if (!is_array($arr)) {
            return [];
        }
        $out = [];
        foreach ($arr as $brand) {
            if (!is_string($brand) && !is_numeric($brand)) {
                continue;
            }
            $brand = trim((string)$brand);
            if ($brand !== '' && !in_array($brand, $out, true)) {
                $out[] = $brand;
            }
        }
        return $out;
    }

    /**
     * 後台傳入的品牌（陣列 / 逗號分隔 / JSON 字串）→ 去重後的陣列
     * @param mixed $value
     * @return array
     */
    private function normalizeBrands($value){
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : explode(',', $value);
        }
        if (!is_array($value)) {
            return [];
        }
        $out = [];
        foreach ($value as $brand) {
            if (!is_string($brand) && !is_numeric($brand)) {
                continue;
            }
            $brand = trim((string)$brand);
            if ($brand !== '' && !in_array($brand, $out, true)) {
                $out[] = $brand;
            }
        }
        return $out;
    }

    /**
     * 通過關鍵字搜索卡包，返回相關卡包
     * @param $data
     * @return array
     */
    public function searchCardsByKeyword($data){
        $limit = empty($data['limit']) ? 10 : $data['limit'];
        $page = ($data['page'] - 1) * $limit;
        $keyword = $data['keyword'];

        $count = $this->where('status', 1)
            ->where(function($q) use ($keyword){
                $q->where('card_sort_name', 'like', '%'.$keyword.'%')
                  ->orWhere('en_card_sort_name', 'like', '%'.$keyword.'%');
            })
            ->count();

        $sorts = $this->where('status', 1)
            ->where('draw_enable', '<>', 0)
            ->where(function($q) use ($keyword){
                $q->where('card_sort_name', 'like', '%'.$keyword.'%')
                  ->orWhere('en_card_sort_name', 'like', '%'.$keyword.'%');
            })
            ->select($this->PrefixAppDetail)
            ->offset($page)->limit($limit)
            ->orderBy('sort','asc')
            ->orderBy('card_sort_id', 'desc')
            ->get()->toArray();

        if(!empty($sorts)){
            $sortIds = array_column($sorts, 'card_sort_id');
            $cardCounts = DB::table('card')
                ->whereIn('card_sort_id', $sortIds)
                ->where('card_status', '<>', -1)
                ->groupBy('card_sort_id')
                ->selectRaw('card_sort_id, count(*) as total_cards, sum(card_stock) as total_stock')
                ->get()
                ->keyBy('card_sort_id');

            $collectedIds = [];
            if(!empty($data['user_id'])){
                $collectedIds = DB::table('card_collect')
                    ->where('user_id', $data['user_id'])
                    ->whereIn('card_sort_id', $sortIds)
                    ->pluck('card_sort_id')
                    ->toArray();
            }
            $collectedIds = array_map('intval', $collectedIds);

            foreach($sorts as $k => $v){
                $sid = $v['card_sort_id'];
                $row = $cardCounts[$sid] ?? null;
                $sorts[$k]['match_count'] = $row ? (int)$row->total_cards : 0;
                $sorts[$k]['undrawn_count'] = $row ? (int)$row->total_stock : 0;
                $sorts[$k]['total_cards'] = $row ? (int)$row->total_cards : 0;
                $sorts[$k]['drawn_count'] = $sorts[$k]['total_cards'] - $sorts[$k]['undrawn_count'];
                $sorts[$k]['is_collected'] = (int)in_array((int)$v['card_sort_id'], $collectedIds);
            }
        }

        $results['count'] = $count;
        $results['data'] = $sorts ?? [];
        $results['keyword'] = $keyword;

        if(!empty($sorts)){
            return $this->success(__('api.list_success'), $results);
        }else{
            return $this->success(__('api.list_null'), $results);
        }
    }

    /**
     * 通過品牌查找擁有該品牌卡牌的卡池（返回格式與 getCardSort 一致）
     * @param $data
     * @return array
     */
    public function getCardSortByBrand($data){
        $brand = $data['brand'] ?? '';
        $tag   = $data['tag'] ?? '';
        $limit = empty($data['limit']) ? 10 : $data['limit'];
        $page = ($data['page'] - 1) * $limit;

        // 依標籤篩選（tag 優先），查 card_tag_order
        if (!empty($tag)) {
            $query = $this->where('status', 1)
                ->where('draw_enable', '<>', 0)
                ->where('card_tag_order', 'like', $this->jsonArrayLikePatternEscaped($tag))
                ->select($this->PrefixAppDetail)
                ->orderBy('sort', 'asc')
                ->orderBy('card_sort_id', 'desc');
            $AllCount = (clone $query)->count();
        } elseif (!empty($brand)) {
            if (self::hasColumnCached('card_sort', 'card_brands')) {
                // 依品牌篩選：只查 card_brands（原生中文）
                $query = $this->where('status', 1)
                    ->where('draw_enable', '<>', 0)
                    ->where('card_brands', 'like', $this->jsonArrayLikePattern($brand))
                    ->select($this->PrefixAppDetail)
                    ->orderBy('sort', 'asc')
                    ->orderBy('card_sort_id', 'desc');
                $AllCount = (clone $query)->count();
            } else {
                // 遷移未執行時退回舊邏輯：按卡牌品牌（card.card_brand）反查卡池
                $cardSortIds = DB::table('card')
                    ->where('card_brand', $brand)
                    ->where('card_status', '<>', -1)
                    ->whereNotNull('card_sort_id')
                    ->distinct()
                    ->pluck('card_sort_id')
                    ->toArray();

                if (empty($cardSortIds)) {
                    return $this->success(__('api.list_null'), [
                        'data' => [], 'count' => 0, 'currentPage' => 1, 'limit' => $limit, 'lastPage' => 1, 'AllCount' => 0,
                    ]);
                }

                $AllCount = count($cardSortIds);

                $query = $this->where('status', 1)
                    ->where('draw_enable', '<>', 0)
                    ->whereIn('card_sort_id', $cardSortIds)
                    ->select($this->PrefixAppDetail)
                    ->orderBy('sort', 'asc')
                    ->orderBy('card_sort_id', 'desc');
            }
        } else {
            // brand 為空，獲取全部卡池
            $AllCount = $this->where('status', 1)->where('draw_enable', '<>', 0)->count();

            $query = $this->where('status', 1)
                ->where('draw_enable', '<>', 0)
                ->select($this->PrefixAppDetail)
                ->orderBy('sort', 'asc')
                ->orderBy('card_sort_id', 'desc');
        }

        $clone = clone $query;
        $count = $clone->count();

        $effects = $query->offset($page)->limit($limit)->get()->toArray();

        $results['AllCount'] = $AllCount;
        $results['count'] = $count;
        $results['currentPage'] = (int)$data['page'];
        $results['limit'] = $limit;
        $results['lastPage'] = (int)ceil($AllCount / $limit);
        $results['data'] = $effects;

        if (!empty($effects)) {
            $sortIds = array_column($effects, 'card_sort_id');
            // 使用 COUNT(CASE WHEN card_stock ...) 直接計算，方式與 getCardSortListWeb 一致
            // SUM(card_stock) 作為 card_inventory（物理庫存），但不直接用於 undrawn_count
            $stockSums = DB::table('card')
                ->whereIn('card_sort_id', $sortIds)
                ->where('card_status', '<>', -1)
                ->groupBy('card_sort_id')
                ->selectRaw('card_sort_id,
                    sum(card_stock) as total_stock,
                    count(*) as total_cards,
                    sum(case when card_stock > 0 then 1 else 0 end) as undrawn_base,
                    sum(case when card_stock < 1 then 1 else 0 end) as drawn_base')
                ->get()
                ->keyBy('card_sort_id');

            // 統計正在其他用戶暫存會話（未確認）中的卡片，從 undrawn_count 中扣除
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

            $collectedIds = [];
            if (!empty($data['user_id'])) {
                $collectedIds = DB::table('card_collect')
                    ->where('user_id', $data['user_id'])
                    ->whereIn('card_sort_id', $sortIds)
                    ->pluck('card_sort_id')
                    ->toArray();
            }
            $collectedIds = array_map('intval', $collectedIds);

            // 當前用戶的各卡池已抽次數 + 每卡池的 draw_limit_per_user + 優惠卡池已抽名單 + 每日限制
            $userDrawCounts = [];
            $drawLimits = [];
            $onceOnlyIds = [];
            $promoDrawnIds = [];
            $dailyLimits = [];
            $todayDrawCounts = [];
            $memberLimits = [];
            $userMemberEnd = 0;
            $requiredEndMap = [];

            // 會員等級限制（多等級 + 每等級可抽張數，兼容舊單等級字段）+ 當前用戶是否達標
            $memberLevelLimits = [];
            $memberInfoMap = [];
            $hasLevelMulti = false;
            $hasLevelLegacy = false;
            try {
                $hasLevelMulti = self::hasColumnCached('card_sort', 'member_level_limits');
                $hasLevelLegacy = self::hasColumnCached('card_sort', 'member_level_limit');
            } catch (\Throwable $e) {
                $hasLevelMulti = false;
                $hasLevelLegacy = false;
            }
            if ($hasLevelMulti || $hasLevelLegacy) {
                $levelCols = ['card_sort_id'];
                if ($hasLevelMulti) {
                    $levelCols[] = 'member_level_limits';
                }
                if ($hasLevelLegacy) {
                    $levelCols[] = 'member_level_limit';
                }
                $levelRows = DB::table('card_sort')
                    ->whereIn('card_sort_id', $sortIds)
                    ->select($levelCols)
                    ->get();
                $levelIds = [];
                foreach ($levelRows as $levelRow) {
                    $limits = self::resolveMemberLevelLimits(
                        $hasLevelMulti ? ($levelRow->member_level_limits ?? null) : null,
                        $hasLevelLegacy ? ($levelRow->member_level_limit ?? 0) : 0
                    );
                    if (!empty($limits)) {
                        $memberLevelLimits[(int)$levelRow->card_sort_id] = $limits;
                        foreach ($limits as $levelItem) {
                            $levelIds[] = (int)$levelItem['member_id'];
                            $memberLimits[(int)$levelRow->card_sort_id] = (int)$levelItem['member_id'];
                        }
                    }
                }
                $levelIds = array_values(array_unique(array_filter($levelIds)));
                if (!empty($levelIds)) {
                    $memberInfoMap = UserMember::memberInfoByIds($levelIds, $this->language);
                }
                if (!empty($data['user_id']) && !empty($levelIds)) {
                    $userIntegral = (int)DB::table('users')->where('user_id', $data['user_id'])->value('user_integral_all');
                    $userMemberEnd = UserMember::memberEndByScore($userIntegral);
                }
            }

            if (!empty($data['user_id'])) {
                if (self::hasTableCached('user_draw_count')) {
                    $userDrawCounts = DB::table('user_draw_count')
                        ->where('user_id', $data['user_id'])
                        ->whereIn('card_sort_id', $sortIds)
                        ->pluck('draw_count', 'card_sort_id')
                        ->map(function ($v) { return (int)$v; })
                        ->toArray();
                }
                if (self::hasColumnCached('card_sort', 'draw_limit_per_user')) {
                    $drawLimits = DB::table('card_sort')
                        ->whereIn('card_sort_id', $sortIds)
                        ->pluck('draw_limit_per_user', 'card_sort_id')
                        ->map(function ($v) { return (int)($v ?? 0); })
                        ->toArray();
                }
                // 每日抽取次數限制 + 當前用戶今日已抽次數（每日0點隨日期切換自然重置）
                if (self::hasColumnCached('card_sort', 'daily_draw_limit')) {
                    $dailyLimits = DB::table('card_sort')
                        ->whereIn('card_sort_id', $sortIds)
                        ->pluck('daily_draw_limit', 'card_sort_id')
                        ->map(function ($v) { return (int)($v ?? 0); })
                        ->toArray();
                }
                if (self::hasTableCached('user_daily_draw_count')) {
                    $todayDrawCounts = DB::table('user_daily_draw_count')
                        ->where('user_id', $data['user_id'])
                        ->whereIn('card_sort_id', $sortIds)
                        ->whereDate('draw_date', date('Y-m-d'))
                        ->pluck('draw_count', 'card_sort_id')
                        ->map(function ($v) { return (int)$v; })
                        ->toArray();
                }
                if (self::hasColumnCached('card_sort', 'is_once_only')) {
                    $onceOnlyIds = DB::table('card_sort')
                        ->whereIn('card_sort_id', $sortIds)
                        ->where('is_once_only', 1)
                        ->pluck('card_sort_id')
                        ->map(function ($v) { return (int)$v; })
                        ->toArray();
                }
                if (self::hasTableCached('user_promo_draw')) {
                    $promoDrawnIds = DB::table('user_promo_draw')
                        ->where('user_id', $data['user_id'])
                        ->whereIn('card_sort_id', $sortIds)
                        ->pluck('card_sort_id')
                        ->map(function ($v) { return (int)$v; })
                        ->toArray();
                }
                // 會員等級限制（多等級 + 每等級可抽張數，兼容舊單等級字段）+ 當前用戶是否達標
            }

            // 標籤順序（獎勵大到小）與每個標籤單獨的中獎動畫（口徑與 /api/getCardsBySort 一致）
            $tagInfoMap = DB::table('card_sort')
                ->whereIn('card_sort_id', $sortIds)
                ->select(['card_sort_id', 'card_tag_order', 'card_tag_animations'])
                ->get()
                ->keyBy('card_sort_id');

            foreach ($results['data'] as $k => $v) {
                $sid = $v['card_sort_id'];
                $stockRow = $stockSums[$sid] ?? null;
                $totalStock = $stockRow ? (int)$stockRow->total_stock : 0;
                $totalCards = $stockRow ? (int)$stockRow->total_cards : 0;
                $undrawnBase = $stockRow ? (int)$stockRow->undrawn_base : 0;
                $drawnBase = $stockRow ? (int)$stockRow->drawn_base : 0;
                $pendingCnt = $pendingCountBySort[$sid] ?? 0;

                // card_inventory = 物理庫存 - 其他用戶暫存中的卡片（真正可抽取的庫存）
                $results['data'][$k]['card_inventory'] = max(0, $totalStock - $pendingCnt);
                // undrawn_count = 庫存 > 0 的卡片數 - 其他用戶暫存中的卡片
                $results['data'][$k]['undrawn_count'] = max(0, $undrawnBase - $pendingCnt);
                $results['data'][$k]['total_cards'] = $totalCards;
                // drawn_count = 庫存 < 1 的卡片數 + 其他用戶暫存中的卡片（已被佔用等同已抽取）
                $results['data'][$k]['drawn_count'] = $drawnBase + $pendingCnt;
                $results['data'][$k]['is_collected'] = (int)in_array((int)$v['card_sort_id'], $collectedIds);
                // 每用戶抽取次數限制 + 剩餘次數（0 = 無限制）
                // draw_limit_per_user > 0 時以配置為準（user_draw_count）；否則優惠卡包看 user_promo_draw
                $once = in_array((int)$sid, $onceOnlyIds, true) ? 1 : 0;
                $limit = $drawLimits[$sid] ?? 0;
                $used  = $userDrawCounts[$sid] ?? 0;
                $promoUsed = in_array((int)$sid, $promoDrawnIds, true) ? 1 : 0;
                // 會員等級限制：多等級可分別配置可抽張數（0 = 該等級不額外限制）
                $levels = $memberLevelLimits[(int)$sid] ?? [];
                $levelDisplay = [];
                foreach ($levels as $levelItem) {
                    $info = $memberInfoMap[(int)$levelItem['member_id']] ?? null;
                    $levelDisplay[] = [
                        'member_id'   => (int)$levelItem['member_id'],
                        'name'        => $info ? $info['name'] : ('ID:' . $levelItem['member_id']),
                        'score_range' => $info ? [(int)$info['member_start'], (int)$info['member_end']] : null,
                        'member_end'  => $info ? (int)$info['member_end'] : 0,
                        'draw_limit'  => (int)$levelItem['draw_limit'],
                    ];
                }
                $levelCfg = empty($levels) ? null : self::matchMemberLevelLimit($levels, $memberInfoMap, $userMemberEnd);
                $levelOk = empty($levels) ? 1 : ($levelCfg ? 1 : 0);
                $levelLimit = $levelCfg ? max(0, (int)$levelCfg['draw_limit']) : 0;
                // 實際生效上限 = 全局上限與等級上限取較小值（0 視為不限）
                $effectiveLimit = $limit;
                if ($levelLimit > 0) {
                    $effectiveLimit = $effectiveLimit > 0 ? min($effectiveLimit, $levelLimit) : $levelLimit;
                }
                $results['data'][$k]['is_once_only'] = $once;
                $results['data'][$k]['draw_limit_per_user'] = $limit;
                $results['data'][$k]['member_level_limits'] = $levelDisplay;
                $results['data'][$k]['member_level_limit'] = !empty($levelDisplay) ? $levelDisplay[0]['member_id'] : 0;
                $results['data'][$k]['member_level_limit_name'] = !empty($levelDisplay)
                    ? implode('/', array_column($levelDisplay, 'name')) : null;
                $results['data'][$k]['member_level_limit_score_range'] = count($levelDisplay) === 1
                    ? $levelDisplay[0]['score_range'] : null;
                $results['data'][$k]['member_level_ok'] = $levelOk;
                $results['data'][$k]['member_level_draw_limit'] = $levelLimit;
                $results['data'][$k]['effective_draw_limit'] = $effectiveLimit;
                if (!$levelOk) {
                    $results['data'][$k]['draw_count']     = $used;
                    $results['data'][$k]['user_drawn']     = $used > 0 ? 1 : 0;
                    $results['data'][$k]['draw_remaining'] = 0;
                } elseif ($effectiveLimit > 0) {
                    $results['data'][$k]['draw_count']     = $used;
                    $results['data'][$k]['user_drawn']     = $used > 0 ? 1 : 0;
                    $results['data'][$k]['draw_remaining'] = max(0, $effectiveLimit - $used);
                } elseif ($once) {
                    $results['data'][$k]['draw_count']     = $promoUsed;
                    $results['data'][$k]['user_drawn']     = $promoUsed;
                    $results['data'][$k]['draw_remaining'] = 1 - $promoUsed;
                } else {
                    $results['data'][$k]['draw_count']     = 0;
                    $results['data'][$k]['user_drawn']     = 0;
                    $results['data'][$k]['draw_remaining'] = -1;
                }
                // 每日抽取次數限制 + 今日已抽/剩餘（0 = 不限制；與總次數限制同時生效）
                $dailyLimit = $dailyLimits[$sid] ?? 0;
                $results['data'][$k]['daily_draw_limit']    = $dailyLimit;
                $results['data'][$k]['draw_today']          = (int)($todayDrawCounts[$sid] ?? 0);
                $results['data'][$k]['draw_today_remaining'] = $dailyLimit > 0
                    ? max(0, $dailyLimit - (int)($todayDrawCounts[$sid] ?? 0))
                    : -1;
                // 本次單次可選的最大張數：卡池可抽庫存 / 每用戶剩餘 / 今日剩餘 / 優惠卡包 / 單次上限 取最小值
                $maxDrawCount = (int)$results['data'][$k]['card_inventory'];
                foreach (['draw_remaining', 'draw_today_remaining'] as $remainField) {
                    if ((int)$results['data'][$k][$remainField] > 0) {
                        $maxDrawCount = min($maxDrawCount, (int)$results['data'][$k][$remainField]);
                    }
                }
                if ($once) {
                    $maxDrawCount = min($maxDrawCount, 1);
                }
                $maxPerOrder = Card::maxDrawCountPerOrder();
                if ($maxPerOrder > 0) {
                    $maxDrawCount = min($maxDrawCount, $maxPerOrder);
                }
                if (!$levelOk) {
                    $maxDrawCount = 0;
                }
                $results['data'][$k]['max_draw_count'] = max(0, $maxDrawCount);
                // 標籤順序與每個標籤單獨的中獎動畫（未設置動畫的標籤用默認動畫 single/ten/hundred）
                // 標籤順序需過濾「預設」（僅為分組，不參與獎勵大小判斷）
                $tagOrderRaw = isset($tagInfoMap[$sid]) ? $tagInfoMap[$sid]->card_tag_order : null;
                $tagOrder = $this->filterDefaultTagFromOrder(!empty($tagOrderRaw) ? json_decode($tagOrderRaw, true) : null);
                $results['data'][$k]['card_tag_order'] = !empty($tagOrder) ? $tagOrder : null;
                $tagAnimRaw = isset($tagInfoMap[$sid]) ? $tagInfoMap[$sid]->card_tag_animations : null;
                $tagAnimations = !empty($tagAnimRaw) ? json_decode($tagAnimRaw, true) : null;
                $results['data'][$k]['card_tag_animations'] = is_array($tagAnimations) ? $tagAnimations : [];
            }

            return $this->success(__('api.list_success'), $results);
        } else {
            return $this->success(__('api.list_null'), $results);
        }
    }

    /**
     * 過濾標籤順序中的「預設」標籤
     * 「預設」僅為分組，不參與獎勵大小判斷；過濾後剩餘標籤即為由大到小排列
     * @param mixed $order json_decode 後的標籤順序
     * @return array
     */
    private function filterDefaultTagFromOrder($order){
        if (!is_array($order)) {
            return [];
        }
        return array_values(array_filter($order, function($tag){
            return is_string($tag) && trim($tag) !== '' && $tag !== '預設' && $tag !== '预设';
        }));
    }
}
