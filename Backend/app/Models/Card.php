<?php

/*
 * File: Card.php
 * Project: NewDemo
 * Created Time: 2026-06-04 04:11:11 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2026-09-10 4:28:15 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2026 成都睿創科技有限公司
 */

namespace App\Models;

use DateTimeInterface;
use App\Models\Base;
use App\Models\Order;
use App\Models\CardOrder;
use App\Models\User;
use App\Models\UserWalletRecord;
use App\Models\SystemInfo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * App\Models\Card
 */
class Card extends Base
{
    use Notifiable;
    protected $table = 'card';
    protected $primaryKey = 'card_id';
    public $timestamps = true;

    /**
     * 为数组 / JSON 序列化准备日期。
     */
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format($this->dateFormat ?: 'Y-m-d H:i:s');
    }

    /**
     * 函數構造
     */
    public function __construct(){
        $this->language = app('global_language') == "en" ? "en_" : "";
        $this->AppDetail = ['card_id','card_sort_id',$this->language.'card_name as card_name','card_img','integral_num',
        'card_type','card_price','card_cost','card_stock',$this->language.'card_describe as card_describe',
        'browse_number','card_status','is_priority','card_tag','deal_condition','created_at','updated_at'];
        //每个值加前缀
       $table=$this->table;
       $this->PrefixAppDetail = array_map(function($item) use ($table) { return $table.'.' . $item ;}, $this->AppDetail);

    }
/*********************************************************全局作用域注册**********************************************************************/

    // protected static function boot(){
    //    parent::boot();
	//    static::addGlobalScope('Card', function (Builder $builder) {
	// 	  return $builder->where('Card.card_status', '<>', -1);
	//    });
    // }

/*********************************************************关联**********************************************************************/



/**********************************************************admin**********************************************************************/
    /**
     * 獲取卡片列表
     * @param $data
     * @param $language
     * @return mixed
     */
    public function getCardListWeb($data,$language){
        $limit = $data['limit'];
        $page = ($data['page'] - 1) * $limit;
        $model = $this->join('card_sort','card.card_sort_id','=','card_sort.card_sort_id')
            ->where('card.card_status', '<>', -1);

        if (!empty($data['card_name'])) {
            $model->where('card.'.$language.'card_name', 'like', '%' . $data['card_name'] . '%');
        }
        if (!empty($data['card_id'])) {
            $model->where('card.card_id', $data['card_id']);
        }
        if (!empty($data['card_barcode'])) {
            $model->where('card.card_barcode', 'like', '%' . $data['card_barcode'] . '%');
        }
        if (!empty($data['card_sort_id'])) {
            $model->where('card.card_sort_id', $data['card_sort_id']);
        }
        if (isset($data['status']) && (is_numeric($data['status']) || !empty($data['status']))) {
            $model->where('card.card_status', $data['status']);
        }
        if (isset($data['stock_type']) && is_numeric($data['stock_type'])) {
            $stockType = (int)$data['stock_type'];
            if ($stockType === 1) {
                // 未抽取：庫存大於0
                $model->where('card.card_stock', '>', 0);
            } elseif ($stockType === 2) {
                // 已抽取：庫存為0
                $model->where('card.card_stock', 0);
            }
        }

        $datas['count'] = (clone $model)->count();

        $datas['data'] = $model->select('card.*','card_sort.'.$language.'card_sort_name as card_sort_name')
            ->offset($page)->limit($limit)
            ->orderBy('card.created_at', 'desc')
            ->get()->toArray();

        return $datas;
    }

    /**
     * 添加卡片
     * @param $album
     * @param $tag
     * @param $Card
     * @return array
     */
    public function addCardWeb($Card,$token){
        DB::beginTransaction();
        $Card['created_at'] = self::getDate();
        $Card['card_stock'] = 1;
        $Card['card_weight'] = 10;
        $CardID = $this->insertGetId($Card);
        $admin = Admins::where('token',$token)->select('admin_id','username')->first();
        if(!empty($CardID)){
            $cn_content = '增加了名稱為：'.$Card['card_name'].' 的卡片！';
            $en_content = '商品名: '.$Card['en_card_name'].' を追加';
            $configInfo = [//增加产品记录操作日誌
                'admin_name' => $admin['username'],
                'admin_id' => $admin['admin_id'],
                'activity_type' => 11,//11新增卡片
                'activity_value' => $cn_content,
                'en_activity_value' => $en_content,
                'created_at' =>  self::getDate()
            ];
            $configInsert = ActivityLog::insert($configInfo);
            DB::commit();
            return $this->success(__('web.add_success'));
        }else{
            DB::rollBack();
            return $this->error(__('web.add_fail'));
        }
    }

    /**
     * 編輯卡片
     * @param $album
     * @param $tag
     * @param $Card
     * @param $token
     * @return array
     */
    public function editCardWeb($Card,$token){
        DB::beginTransaction();
        $admin = Admins::where('token',$token)->select('admin_id','username')->first();
        $oldCost = $this->where('card_id',$Card['card_id'])->value('card_cost');
        unset($Card['is_min_stock']); unset($Card['card_relation']); unset($Card['card_specs_key']);
        // $weightMap = ['N' => 10, 'R' => 40, 'SR' => 70, 'SSR' => 90];
        $weightMap = ['N' => 10, 'R' => 10, 'SR' => 10, 'SSR' => 10];
        $Card['card_weight'] = 10;
        $CardUpdate = $this->where('card_id',$Card['card_id'])->update($Card);
        if(!$CardUpdate){
            DB::rollBack();
            return $this->error(__('web.edit_fail'));
        }
        if($oldCost != $Card['card_cost']){
            $costInsert = ActivityLog::insert([
                'admin_name' => $admin['username'],
                'admin_id' => $admin['admin_id'],
                'activity_type' => 5,
                'activity_value' => 'ID為'.$Card['card_id'].'的卡片，更改成本為： '.$Card['card_cost'],
                'en_activity_value' => '商品ID: '.$Card['card_id'].' コスト変更: '.$Card['card_cost'],
                'created_at' => self::getDate()
            ]);
            if(!$costInsert){
                DB::rollBack();
                return $this->error(__('web.edit_fail'));
            }
        }
        DB::commit();
        return $this->success(__('web.edit_success'));
    }

    /**
     * 刪除卡片
     * @param $CardID
     * @return array
     */
    public function deleteCardWeb($CardID){
        $data = self::softDelete('card','card_id',$CardID,'card_status',-1);
        if($data){
            return $this->success(__('web.delete_success'));
        }else{
            return $this->error(__('web.delete_fail'));
        }
    }





/**********************************************************api**********************************************************************//*





    // @deprecated 已停用：抽卡已合併為 confirmDrawOrder（card_sort_id+count，直接錢包支付建單），此暫存/預覽方法不再有任何路由調用。
    /**
     * @param $data
     * @return array
     * 抽取卡片列表（僅暫存，不扣款不入訂單）
     * 首次抽取傳入 session_id=0，返回新的 session_id
     * 不滿意可傳入 session_id 重新抽取覆蓋暫存數據
     */
    public function getDrawCardList($data){
        // 驗證抽取數量
        $count = (int)$data['count'];
        if ($count <= 0) {
            return $this->error(__('api.param_error'));
        }

        $sessionId = isset($data['session_id']) ? (int)$data['session_id'] : 0;

        // 獲取卡包價格信息
        $sortInfo = DB::table('card_sort')
            ->where('card_sort_id', $data['card_sort_id'])
            ->where('status', 1)
            ->select('card_draw_price')
            ->first();

        if (empty($sortInfo)) {
            return $this->error(__('api.param_error'));
        }
        // 計算總花費
        $totalCost = round((float)$sortInfo->card_draw_price * $count, 2);

        // 使用資料庫交易 + 排他鎖，防止並發抽卡導致重複卡片
        DB::beginTransaction();
        try {
            // 對該卡包類型的所有活躍抽卡會話加上排他鎖（FOR UPDATE）
            // 這會讓同時請求的同類抽卡操作排隊處理，確保原子性
            DB::table('draw_session')
                ->where('card_sort_id', $data['card_sort_id'])
                ->where('status', 0)
                ->where('expires_at', '>', now())
                ->when($sessionId > 0, function ($q) use ($sessionId) {
                    return $q->where('session_id', '<>', $sessionId);
                })
                ->lockForUpdate()
                ->get();

            // 查詢可抽取的卡片列表（只篩選庫存 > 0 的卡片）
            $where[] = array('card.card_status','=',1);
            $where[] = array('card.card_stock','>',0);
            if (!empty($data['card_name'])) {
                $where[] = array('card.'.$this->language .'card_name', 'like', '%' . $data['card_name'] . '%');
            }
            if (!empty($data['card_sort_id'])) {
                $where[] = array('card.card_sort_id', '=',  $data['card_sort_id'] );
            }

            // 排除其他用戶正在暫存（未確認）中的卡片，防止多用戶同時抽到相同卡片
            $pendingCardIds = DB::table('draw_session')
                ->where('status', 0)
                ->where('expires_at', '>', now())
                ->when($sessionId > 0, function ($q) use ($sessionId) {
                    return $q->where('session_id', '<>', $sessionId);
                })
                ->get(['draw_data'])
                ->flatMap(function ($session) {
                    $cards = json_decode($session->draw_data, true);
                    return $cards ? array_column($cards, 'card_id') : [];
                })
                ->filter()
                ->unique()
                ->values()
                ->toArray();

            $effects = $this->join('card_sort','card.card_sort_id','=','card_sort.card_sort_id')
                ->select('card.*','card_sort.'.$this->language .'card_sort_name as card_sort_name')
                ->where($where)
                ->when(!empty($pendingCardIds), function ($q) use ($pendingCardIds) {
                    return $q->whereNotIn('card.card_id', $pendingCardIds);
                })
                ->orderBy('card.created_at', 'desc')->get()->toArray();

            // 判斷可抽取卡片數量是否足夠
            if (count($effects) < $count) {
                DB::rollBack();
                return $this->error(__('api.insufficient_cards'));
            }

            // 按概率權重抽取指定數量卡片
            $cards = $this->drawCards($effects, $count, $data['card_sort_id']);
            if (empty($cards)) {
                DB::rollBack();
                return $this->success(__('api.list_null'), []);
            }

            $now = date('Y-m-d H:i:s');
            $expiresAt = date('Y-m-d H:i:s', strtotime('+30 minutes'));

            // 如果傳入了session_id，覆蓋更新已有的暫存數據
            if ($sessionId > 0) {
                $existing = DB::table('draw_session')
                    ->where('session_id', $sessionId)
                    ->where('user_id', $data['user_id'])
                    ->where('status', 0)
                    ->first();

                if (empty($existing)) {
                    DB::rollBack();
                    return $this->error(__('api.param_error'));
                }

                // 驗證重新抽取的參數與原session一致
                if ($existing->card_sort_id != $data['card_sort_id'] || $existing->count != $count) {
                    DB::rollBack();
                    return $this->error(__('api.param_error'));
                }

                // 更新暫存的卡片數據和過期時間
                DB::table('draw_session')->where('session_id', $sessionId)->update([
                    'draw_data' => json_encode($cards),
                    'draw_count' => DB::raw('draw_count + 1'),
                    'expires_at' => $expiresAt,
                    'updated_at' => $now
                ]);

                $drawCount = DB::table('draw_session')->where('session_id', $sessionId)->value('draw_count');
            } else {
                // 首次抽取，新建暫存記錄
                $sessionId = DB::table('draw_session')->insertGetId([
                    'user_id' => $data['user_id'],
                    'card_sort_id' => $data['card_sort_id'],
                    'count' => $count,
                    'total_cost' => $totalCost,
                    'draw_data' => json_encode($cards),
                    'draw_count' => 1,
                    'status' => 0,
                    'expires_at' => $expiresAt,
                    'created_at' => $now,
                    'updated_at' => $now
                ]);

                $drawCount = 1;
            }

            DB::commit();

            // 檢查抽中的卡片是否包含最高稀有度標籤（過濾「預設」後的 card_tag_order 第一個標籤）
            $_winDebug = null;
            try {
                // 查詢用戶名稱
                $_userName = DB::table('users')->where('user_id', $data['user_id'])->value('user_name') ?? '';
                $_tagOrderStr = DB::table('card_sort')
                    ->where('card_sort_id', $data['card_sort_id'])
                    ->value('card_tag_order');
                if ($_tagOrderStr) {
                    // 「預設」僅為分組，不參與獎勵大小排序，需先過濾
                    $_tagOrder = $this->filterDefaultTagFromOrder(json_decode($_tagOrderStr, true));
                    if (!empty($_tagOrder)) {
                        $_topTag = $_tagOrder[0]; // 過濾「預設」後的第一個標籤為最高稀有度
                        $_winInserted = false;
                        foreach ($cards as $_dc) {
                            $_dcPrice = (float)($_dc['card_price'] ?? 0);
                            // 稀有度標籤 + 價值門檻（避免低價白卡佔用稀有標籤也發通知）
                            if (!empty($_dc['card_tag']) && $_dc['card_tag'] === $_topTag && $_dcPrice > 1000) {
                                \Illuminate\Support\Facades\DB::table('system_info')->insert([
                                    'user_id' => $data['user_id'],
                                    'system_type' => 4,
                                    'system_content' => '恭喜用戶' . $_userName . '獲得稀有卡牌' . ($_dc['card_name'] ?? ''),
                                    'en_system_content' => 'Congratulations to ' . $_userName . ' for getting a rare card: ' . ($_dc['en_card_name'] ?? $_dc['card_name'] ?? ''),
                                    'order_id' => $sessionId,
                                    'is_read' => 1,
                                    'system_status' => 1,
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'updated_at' => date('Y-m-d H:i:s')
                                ]);
                                $_winInserted = true;
                                break;
                            }
                        }
                        $_winDebug = [
                            'card_tag_order' => $_tagOrderStr,
                            'topTag' => $_topTag,
                            'drawn_tags' => array_map(function($c) { return $c['card_tag'] ?? 'N/A'; }, $cards),
                            'has_inserted' => $_winInserted
                        ];
                    } else {
                        $_winDebug = ['card_tag_order' => $_tagOrderStr, 'decode_failed' => true, 'decoded' => $_tagOrder];
                    }
                } else {
                    $_winDebug = 'card_tag_order IS NULL';
                }
            } catch (\Throwable $_winE) {
                $_winDebug = 'ERROR: ' . $_winE->getMessage();
            }

            $results = [
                'session_id' => (int)$sessionId,
                'cards' => $cards,
                'total_cost' => $totalCost,
                'expires_at' => $expiresAt,
                'draw_count' => (int)$drawCount,
                '_win_debug' => $_winDebug
            ];
            return $this->success(__('api.list_success'), $results);
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('confirmDrawOrder outer catch', ['error'=>$e->getMessage(), 'trace'=>$e->getTraceAsString()]);
            return $this->error(__('api.param_error'));
        }
    }

    /**
     * @param $data
     * @return array
     * 確認抽卡結果，扣款並創建訂單
     */
    public function confirmDrawOrder($data){
        // 臨時開關：.env 設 DRAW_ENABLE=false 可整體關閉抽卡入口（改後需 config:clear && config:cache）
        if (!config('services.basics.draw_enable', true)) {
            return $this->error(__('api.draw_disabled'));
        }

        // 併發壓測優化：拆「短事務(扣款/扣庫存/佔位)」與「建單(不持卡鎖)」兩階段
        $count = (int)($data['count'] ?? 0);
        $cardSortId = (int)($data['card_sort_id'] ?? 0);
        if ($count <= 0 || $cardSortId <= 0) {
            return $this->error(__('api.param_error'));
        }
        $userId = (int)($data['user_id'] ?? 0);
        if ($userId <= 0) {
            return $this->error(__('api.param_error'));
        }

        // 單次抽取張數上限（app_settings.max_draw_count_per_order，0 或未配置 = 不限制）
        $maxPerOrder = self::maxDrawCountPerOrder();
        if ($maxPerOrder > 0 && $count > $maxPerOrder) {
            return $this->error(__('api.draw_count_per_order_exceeded', ['max' => $maxPerOrder]));
        }

        // 卡包價格 + 該卡池的抽卡開關
        $sortColumns = ['card_draw_price', 'draw_enable'];
        $hasOnceOnly = false;
        $hasDrawLimit = false;
        $hasDailyLimit = false;
        $hasMemberLimit = false;
        $hasMemberLimits = false;
        try {
            $hasOnceOnly = self::hasColumnCached('card_sort', 'is_once_only');
            $hasDrawLimit = self::hasColumnCached('card_sort', 'draw_limit_per_user');
            $hasDailyLimit = self::hasColumnCached('card_sort', 'daily_draw_limit');
            $hasMemberLimit = self::hasColumnCached('card_sort', 'member_level_limit');
            $hasMemberLimits = self::hasColumnCached('card_sort', 'member_level_limits');
        } catch (\Throwable $e) {
            $hasOnceOnly = false;
            $hasDrawLimit = false;
            $hasDailyLimit = false;
            $hasMemberLimit = false;
            $hasMemberLimits = false;
        }
        if ($hasOnceOnly) {
            $sortColumns[] = 'is_once_only';
        }
        if ($hasDrawLimit) {
            $sortColumns[] = 'draw_limit_per_user';
        }
        if ($hasDailyLimit) {
            $sortColumns[] = 'daily_draw_limit';
        }
        if ($hasMemberLimit) {
            $sortColumns[] = 'member_level_limit';
        }
        if ($hasMemberLimits) {
            $sortColumns[] = 'member_level_limits';
        }
        $sortInfo = DB::table('card_sort')
            ->where('card_sort_id', $cardSortId)
            ->where('status', 1)
            ->select($sortColumns)
            ->first();
        if (empty($sortInfo)) {
            return $this->error(__('api.param_error'));
        }
        // 該卡池抽卡入口已關閉（card_sort.draw_enable = 0）
        if (isset($sortInfo->draw_enable) && (int)$sortInfo->draw_enable !== 1) {
            return $this->error(__('api.draw_disabled'));
        }
        // 會員等級限制：支援多等級（member_level_limits JSON）+ 每等級可抽張數，兼容舊單等級字段
        // 用戶當前檔位(member_end) 必須命中配置中的某一等級，否則不可抽
        $levelDrawLimit = 0;
        $levelLimits = CardSort::resolveMemberLevelLimits(
            $hasMemberLimits ? ($sortInfo->member_level_limits ?? null) : null,
            $hasMemberLimit ? ($sortInfo->member_level_limit ?? 0) : 0
        );
        if (!empty($levelLimits)) {
            $memberInfoMap = UserMember::memberInfoByIds(array_column($levelLimits, 'member_id'));
            $userIntegral = (int)DB::table('users')->where('user_id', $userId)->value('user_integral_all');
            $userEnd = UserMember::memberEndByScore($userIntegral);
            $matchedLevel = CardSort::matchMemberLevelLimit($levelLimits, $memberInfoMap, $userEnd);
            if (empty($matchedLevel)) {
                return $this->error(__('api.member_level_limit'));
            }
            $levelDrawLimit = max(0, (int)$matchedLevel['draw_limit']);
        }
        // 優惠卡包：App 端只提供單抽按鈕，後端同樣強制只能單抽
        $onceOnly = $hasOnceOnly && isset($sortInfo->is_once_only) && (int)$sortInfo->is_once_only === 1;
        if ($onceOnly && $count !== 1) {
            return $this->error(__('api.promo_once_single_only'));
        }
        // 每用戶抽取張數上限（統計口徑：按卡牌張數累計，單抽=1張、10連=10張、100連=100張）：
        // 1) draw_limit_per_user > 0（顯式配置）時以該配置為準——即使同時開了優惠卡包，也能抽 N 張；
        // 2) draw_limit_per_user = 0 且為優惠卡包時，視為 1 張，走 user_promo_draw 唯一搶佔（舊邏輯）；
        // 3) 其餘情況無限制。
        $configuredLimit = ($hasDrawLimit && isset($sortInfo->draw_limit_per_user))
            ? max(0, (int)$sortInfo->draw_limit_per_user) : 0;
        // 全局上限與會員等級上限同時生效時取較小值（0 視為不限）
        $effectiveLimits = array_values(array_filter([$configuredLimit, $levelDrawLimit], function ($v) {
            return $v > 0;
        }));
        $drawLimitPerUser = empty($effectiveLimits) ? 0 : min($effectiveLimits);
        $usePromoClaim = ($drawLimitPerUser <= 0 && $onceOnly);
        if ($usePromoClaim) {
            $drawLimitPerUser = 1;
        }
        // 每日抽取張數限制（與總張數限制互相獨立、同時生效）：>0 時按「今天」的 user_daily_draw_count 統計，
        // 每日0點隨日期切換自然重置，無需定時任務
        $dailyDrawLimit = ($hasDailyLimit && isset($sortInfo->daily_draw_limit))
            ? max(0, (int)$sortInfo->daily_draw_limit) : 0;
        $hasDailyCountTable = self::hasTableCached('user_daily_draw_count');
        // 抽前檢查張數上限
        if ($usePromoClaim && self::hasTableCached('user_promo_draw')) {
            $promoExists = DB::table('user_promo_draw')
                ->where('user_id', $userId)
                ->where('card_sort_id', $cardSortId)
                ->exists();
            if ($promoExists) {
                return $this->error(__('api.promo_once_limit'));
            }
        } elseif ($drawLimitPerUser > 0 && self::hasTableCached('user_draw_count')) {
            $currentCount = (int)DB::table('user_draw_count')
                ->where('user_id', $userId)
                ->where('card_sort_id', $cardSortId)
                ->value('draw_count');
            if ($currentCount + $count > $drawLimitPerUser) {
                $remain = max(0, $drawLimitPerUser - $currentCount);
                return $this->error(__('api.draw_limit_exceeded', ['remain' => $remain]));
            }
        }
        // 抽前檢查每日張數上限
        if ($dailyDrawLimit > 0 && $hasDailyCountTable) {
            $todayUsed = (int)DB::table('user_daily_draw_count')
                ->where('user_id', $userId)
                ->where('card_sort_id', $cardSortId)
                ->whereDate('draw_date', date('Y-m-d'))
                ->value('draw_count');
            if ($todayUsed + $count > $dailyDrawLimit) {
                return $this->error(__('api.daily_draw_limit_exceeded', ['remain' => 0]));
            }
        }
        $totalCost = round((float)$sortInfo->card_draw_price * $count, 2);
        if ($totalCost <= 0) {
            return $this->error(__('api.param_error'));
        }

        // ---- 階段 1：選卡（鎖外） + 扣款/扣庫存/寫 task（Redis 鎖內）----
        // Redis 鎖按 user 粒度，同卡池不同用戶可並行；僅同用戶重複提交被擋
        // MySQL 行鎖（user + card）提供最終一致性，Redis 鎖只做「削峯」
        $cards = $this->pickCardsLocked($cardSortId, $count);
        if (count($cards) < $count) {
            return $this->error(__('api.insufficient_cards'));
        }
        try {
            $reserve = Cache::lock('draw:lock:' . $cardSortId . ':' . $userId, 3)
                ->block(1, function () use ($userId, $cardSortId, $count, $totalCost, $cards, $usePromoClaim, $drawLimitPerUser, $dailyDrawLimit, $hasDailyCountTable) {
                    return $this->reserveCardsAndCharge($userId, $cardSortId, $count, $totalCost, $cards, $usePromoClaim, $drawLimitPerUser, $dailyDrawLimit, $hasDailyCountTable);
                });
        } catch (\Illuminate\Contracts\Cache\LockTimeoutException $e) {
            return $this->error('操作過於頻繁，請稍後重試');
        } catch (\Throwable $e) {
            \Log::error('confirmDrawOrder lock exception', ['user_id'=>$userId, 'error'=>$e->getMessage()]);
            return $this->error('系統繁忙，請重試');
        }

        if (empty($reserve['ok'])) {
            return $this->error($reserve['msg'] ?? __('api.balance_fail'));
        }

        // ---- 階段 2：丟隊列異步建單，HTTP 立即返回（建單由 queue worker 執行）----
        \Illuminate\Support\Facades\Bus::dispatch(
            new \App\Jobs\BuildCardOrderJob((int)$reserve['task_id'])
        );

        // 資金流水日誌：記錄扣款後餘額，用於核對「抽了卡卻沒扣幣」類問題
        \Log::info('draw order reserved', [
            'user_id'       => $userId,
            'card_sort_id'  => $cardSortId,
            'count'         => $count,
            'total_cost'    => $totalCost,
            'task_id'       => (int)$reserve['task_id'],
            'balance_after' => User::where('user_id', $userId)->value('user_balance'),
        ]);

        return $this->success(__('api.list_success'), [
            'status'     => 'processing',
            'task_id'    => (int)$reserve['task_id'],
            'order_ids'  => [],
            'cards'      => $reserve['cards'],
            'total_cost' => $totalCost
        ]);
    }

    /**
     * 階段1（鎖內）：行鎖複核→扣庫存→扣餘額→寫建單任務
     * 選卡已在鎖外完成（$cards 由呼叫方傳入），故鎖內僅毫秒級操作
     */
    private function reserveCardsAndCharge(int $userId, int $cardSortId, int $count, float $totalCost, array $cards, bool $usePromoClaim = false, int $drawLimitPerUser = 0, int $dailyDrawLimit = 0, bool $hasDailyCountTable = false): array
    {
        DB::beginTransaction();
        try {
            // ---- 額外獎勵：卡池全局第 N 張觸發 ----
            // 與扣款/扣庫存同事務：drawn_total 自增與 bonus 發放一起成敗
            // 但獎勵卡庫存不足時跳過（不影響付費抽卡），只寫 log 記錄
            $bonusCards = [];
            try {
                $bonusCards = $this->triggerSortBonusRulesTx($userId, $cardSortId, $count);
            } catch (\Throwable $e) {
                // bonus 邏輯異常不能讓整筆抽卡失敗，記日誌後繼續
                \Log::warning('triggerSortBonusRulesTx exception', [
                    'user_id' => $userId, 'card_sort_id' => $cardSortId, 'error' => $e->getMessage(),
                ]);
            }

            // F-12 行鎖校驗錢包餘額，防止並發超扣
            $lockedUser = User::where('user_id', $userId)
                ->lockForUpdate()
                ->select('user_balance')
                ->first();
            if (empty($lockedUser)) {
                DB::rollBack();
                return ['ok' => false, 'msg' => __('api.user_notexist')];
            }
            if ((float)$lockedUser->user_balance < $totalCost) {
                DB::rollBack();
                return ['ok' => false, 'msg' => __('api.balance_fail')];
            }

            // 選卡已於鎖外完成，這裡直接使用
            if (count($cards) < $count) {
                DB::rollBack();
                return ['ok' => false, 'msg' => __('api.insufficient_cards')];
            }

            // 未配置 draw_limit_per_user 的優惠卡包：以 (user_id, card_sort_id) 唯一索引原子搶佔「唯一一次」資格，
            // 併發/重複提交時只有一筆能寫入成功，其餘在扣款前就被攔下；失敗會隨事務一起回滾。
            if ($usePromoClaim) {
                if (!self::hasTableCached('user_promo_draw')) {
                    \Log::warning('user_promo_draw table missing, promo once limit skipped', ['card_sort_id' => $cardSortId]);
                } else {
                    $claimed = DB::table('user_promo_draw')->insertOrIgnore([
                        'user_id'      => $userId,
                        'card_sort_id' => $cardSortId,
                        'created_at'   => date('Y-m-d H:i:s'),
                        'updated_at'   => date('Y-m-d H:i:s'),
                    ]);
                    if ($claimed === 0) {
                        DB::rollBack();
                        return ['ok' => false, 'msg' => __('api.promo_once_limit')];
                    }
                }
            }

            // 每用戶抽取張數限制：事務內二次校驗 + 原子累加，與扣款同成敗
            // 統計口徑：按卡牌張數累計（單抽=1張、10連=10張、100連=100張）
            if ($drawLimitPerUser > 0 && self::hasTableCached('user_draw_count')) {
                $now = date('Y-m-d H:i:s');
                DB::statement(
                    'INSERT INTO user_draw_count (user_id, card_sort_id, draw_count, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE
                       draw_count = draw_count + ?,
                       updated_at = VALUES(updated_at)',
                    [$userId, $cardSortId, $count, $now, $now, $count]
                );
                // 事務內二次讀取確認未超限（併發場景：鎖外檢查通過但多請求同時進來）
                $updatedCount = (int)DB::table('user_draw_count')
                    ->where('user_id', $userId)
                    ->where('card_sort_id', $cardSortId)
                    ->value('draw_count');
                if ($updatedCount > $drawLimitPerUser) {
                    DB::rollBack();
                    return ['ok' => false, 'msg' => __('api.draw_limit_exceeded', ['remain' => 0])];
                }
            }

            // 每日抽取張數限制：與總張數限制互相獨立、同時生效
            // 以 draw_date = 今天 的行統計，跨天寫入新行自然重置（每日0點生效，無需定時任務）
            if ($dailyDrawLimit > 0 && $hasDailyCountTable) {
                $now = date('Y-m-d H:i:s');
                $today = date('Y-m-d');
                DB::statement(
                    'INSERT INTO user_daily_draw_count (user_id, card_sort_id, draw_date, draw_count, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE
                       draw_count = draw_count + ?,
                       updated_at = VALUES(updated_at)',
                    [$userId, $cardSortId, $today, $count, $now, $now, $count]
                );
                // 事務內二次讀取確認未超限（併發場景）
                $todayUsed = (int)DB::table('user_daily_draw_count')
                    ->where('user_id', $userId)
                    ->where('card_sort_id', $cardSortId)
                    ->whereDate('draw_date', $today)
                    ->value('draw_count');
                if ($todayUsed > $dailyDrawLimit) {
                    DB::rollBack();
                    return ['ok' => false, 'msg' => __('api.daily_draw_limit_exceeded', ['remain' => 0])];
                }
            }

            $drawnIds = array_values(array_unique(array_map(function ($c) {
                return (int)$c['card_id'];
            }, $cards)));

            // 行鎖複核庫存（按 card_id 排序鎖定，降低死鎖機率）
            $lockedRows = DB::table('card')
                ->whereIn('card_id', $drawnIds)
                ->where('card_stock', '>', 0)
                ->orderBy('card_id')
                ->lockForUpdate()
                ->get(['card_id']);
            if ($lockedRows->count() !== count($drawnIds)) {
                // 並發搶卡導致部分庫存已被扣光：回報可重抽
                DB::rollBack();
                return ['ok' => false, 'msg' => __('api.insufficient_cards'), 'retry' => true];
            }

            // F-13 條件式扣庫存（一條 UPDATE）
            $stockAffected = DB::table('card')
                ->whereIn('card_id', $drawnIds)
                ->where('card_stock', '>', 0)
                ->decrement('card_stock', 1);
            if ($stockAffected !== count($drawnIds)) {
                DB::rollBack();
                return ['ok' => false, 'msg' => '庫存不足，請重新抽取'];
            }

            // F-12 條件式扣餘額
            $affected = User::where('user_id', $userId)
                ->where('user_balance', '>=', $totalCost)
                ->decrement('user_balance', $totalCost);
            if ($affected === 0) {
                DB::rollBack();
                return ['ok' => false, 'msg' => __('api.balance_fail')];
            }

            $now = date('Y-m-d H:i:s');

            // 建單任務（階段 2 的持久化依據，確保「錢扣了必有單」）
            // 先寫任務、再寫扣款流水，讓流水能帶上 draw_task_id，供後台「查賬」精確對賬
            $taskId = DB::table('card_draw_tasks')->insertGetId([
                'user_id'      => $userId,
                'card_sort_id' => $cardSortId,
                'total_cost'   => $totalCost,
                'cards_json'   => json_encode($cards, JSON_UNESCAPED_UNICODE),
                'status'       => 0,
                'retry_times'  => 0,
                'created_at'   => $now,
                'updated_at'   => $now
            ]);
            if (empty($taskId)) {
                DB::rollBack();
                return ['ok' => false, 'msg' => __('api.network_error')];
            }

            // 錢包流水（扣款憑證）
            // payment_mode=7 抽卡專用（區別於 1=PayPal/2=Stripe/3=Wonder/4=餘額/5=鑽石/6=鑽石兌換）
            UserWalletRecord::insert([
                'user_id'           => $userId,
                'wallet_type'       => 1,
                'payment_mode'      => 7,
                'wallet_explain'    => '抽取卡片',
                'en_wallet_explain' => 'カードを引く',
                'wallet_money'      => $totalCost,
                'draw_task_id'      => (int)$taskId,
                'created_at'        => $now,
                'updated_at'        => $now
            ]);

            DB::commit();
            // 獎勵卡已在事務內扣庫存並寫入 log，合併進返回數組 + 回填 draw_task_id
            if (!empty($bonusCards)) {
                // 把已寫入的 bonus_log（draw_task_id 當初預留 0）回填本次 task_id
                $ruleIds = array_values(array_unique(array_column($bonusCards, 'bonus_rule_id')));
                if (!empty($ruleIds) && self::hasTableCached('card_sort_bonus_log')) {
                    DB::table('card_sort_bonus_log')
                        ->where('card_sort_id', $cardSortId)
                        ->whereIn('rule_id', $ruleIds)
                        ->where('user_id', $userId)
                        ->where('draw_task_id', 0)
                        ->update(['draw_task_id' => (int)$taskId, 'updated_at' => $now]);
                }
                $cards = array_merge($cards, $bonusCards);
            }
            return ['ok' => true, 'cards' => $cards, 'task_id' => (int)$taskId];
        } catch (\Throwable $e) {
            DB::rollBack();
            return ['ok' => false, 'msg' => $e->getMessage()];
        }
    }

    /**
     * 卡池額外獎勵觸發（在已經開啟的 DB 事務內執行）
     * 邏輯：
     *  1. 原子自增 card_sort.drawn_total（全局已抽張數計數器）
     *  2. 根據 before/after 區間計算本次跨越了哪些觸發點
     *  3. 對每個 (規則, 觸發點) 寫 bonus_log（唯一鍵實現全局只發一次）
     *  4. 成功寫入且獎勵卡有庫存 → 扣庫存 + 返回卡信息
     *  5. 獎勵卡庫存不足 → 僅記 remark，不回滾事務
     * @return array<int, array> 獎勵卡數組（帶 is_bonus / bonus_position 標記），為空則無獎勵
     */
    private function triggerSortBonusRulesTx(int $userId, int $cardSortId, int $count): array
    {
        if ($count <= 0) {
            return [];
        }
        // 功能關閉守衛：新列/新表不存在時整體跳過，不影響既有抽卡
        $hasDrawnTotal = self::hasColumnCached('card_sort', 'drawn_total');
        $hasRuleTable = self::hasTableCached('card_sort_bonus_rule');
        $hasLogTable = self::hasTableCached('card_sort_bonus_log');
        if (!$hasDrawnTotal || !$hasRuleTable || !$hasLogTable) {
            return [];
        }

        // 原子自增 drawn_total 並取回自增後值（LAST_INSERT_ID 無需 SELECT 鎖行）
        $affected = DB::statement(
            'UPDATE card_sort SET drawn_total = LAST_INSERT_ID(GREATEST(drawn_total, 0) + ?) WHERE card_sort_id = ?',
            [$count, $cardSortId]
        );
        // 使用另一條連接的 LAST_INSERT_ID 不可靠，改為讀回最新值
        $after = (int)DB::table('card_sort')->where('card_sort_id', $cardSortId)->value('drawn_total');
        if ($affected === false) {
            // 自增失敗（卡池不存在等），回寫 0 後直接返回
            DB::table('card_sort')->where('card_sort_id', $cardSortId)->update(['drawn_total' => 0]);
            return [];
        }
        $before = max(0, $after - $count);

        // 讀取本池所有啟用規則
        $rules = DB::table('card_sort_bonus_rule')
            ->where('card_sort_id', $cardSortId)
            ->where('is_active', 1)
            ->orderBy('trigger_at', 'asc')
            ->get(['rule_id', 'trigger_at', 'card_id']);
        if ($rules->isEmpty()) {
            return [];
        }

        $bonusCards = [];
        $now = date('Y-m-d H:i:s');
        $bonusCardIds = []; // 後面批量查一次獎勵卡信息

        foreach ($rules as $rule) {
            $n = (int)$rule->trigger_at;
            if ($n <= 0) {
                continue;
            }
            // 本次跨越的觸發點個數：floor(after/N) - floor(before/N)
            $timesHit = intdiv($after, $n) - intdiv($before, $n);
            for ($i = 1; $i <= $timesHit; $i++) {
                $position = $before + $i * $n; // 第 before+N / before+2N / ... 張
                $bonusCardIds[] = [
                    'rule_id'          => (int)$rule->rule_id,
                    'trigger_position' => $position,
                    'card_id'          => (int)$rule->card_id,
                ];
            }
        }
        if (empty($bonusCardIds)) {
            return [];
        }

        // 一次查獎勵卡信息（去重後再查）
        $uniqueCardIds = array_values(array_unique(array_column($bonusCardIds, 'card_id')));
        $cardInfoMap = [];
        if (!empty($uniqueCardIds)) {
            $cardCols = ['card_id', 'card_sort_id', 'card_name', 'en_card_name', 'card_img',
                'card_price', 'card_type', 'card_tag', 'card_stock'];
            $cardRows = DB::table('card')
                ->whereIn('card_id', $uniqueCardIds)
                ->where('card_status', 1)
                ->get($cardCols);
            foreach ($cardRows as $cr) {
                $cardInfoMap[(int)$cr->card_id] = (array)$cr;
            }
        }

        foreach ($bonusCardIds as $b) {
            $ruleId = $b['rule_id'];
            $position = $b['trigger_position'];
            $bonusCardId = $b['card_id'];

            // 全局唯一鍵 (rule_id, trigger_position)：插入成功=首次觸發，衝突=已發過→跳過
            $inserted = DB::table('card_sort_bonus_log')->insertOrIgnore([
                'card_sort_id'     => $cardSortId,
                'rule_id'          => $ruleId,
                'trigger_position' => $position,
                'user_id'          => $userId,
                'card_id'          => $bonusCardId,
                'draw_task_id'     => 0, // 後續 reserveCardsAndCharge 才產生 task_id，這裡先留空
                'remark'           => '',
                'created_at'       => $now,
                'updated_at'       => $now,
            ]);
            if ($inserted === 0) {
                continue; // 已發過，跳過
            }

            // 獎勵卡必須存在、在本池、庫存 > 0
            $info = $cardInfoMap[$bonusCardId] ?? null;
            if (empty($info) || (int)($info['card_stock'] ?? 0) <= 0) {
                DB::table('card_sort_bonus_log')
                    ->where('rule_id', $ruleId)
                    ->where('trigger_position', $position)
                    ->update(['remark' => '獎勵卡庫存不足，已跳過', 'updated_at' => $now]);
                continue;
            }
            // 行鎖校驗 + 條件式扣庫存（必須 > 0 才扣）
            $lockedBonus = DB::table('card')
                ->where('card_id', $bonusCardId)
                ->where('card_stock', '>', 0)
                ->lockForUpdate()
                ->decrement('card_stock', 1);
            if ($lockedBonus === 0) {
                DB::table('card_sort_bonus_log')
                    ->where('rule_id', $ruleId)
                    ->where('trigger_position', $position)
                    ->update(['remark' => '獎勵卡庫存不足（扣減時檢查），已跳過', 'updated_at' => $now]);
                continue;
            }

            // 成功：標記 is_bonus + bonus_position，併入返回數組
            $bonusCards[] = array_merge($info, [
                'is_bonus'        => 1,
                'bonus_position'  => $position,
                'bonus_rule_id'   => $ruleId,
            ]);
        }

        if (!empty($bonusCards)) {
            \Log::info('sort bonus triggered', [
                'user_id' => $userId, 'card_sort_id' => $cardSortId,
                'count' => $count, 'before' => $before, 'after' => $after,
                'bonus_count' => count($bonusCards),
            ]);
        }
        return $bonusCards;
    }

    /**
     * 重建某卡池的預設順序表（draw_preset_items）
     * 由管理端保存預設、自動生成預設、artisan 命令與抽卡懶載入時呼叫
     * @return int 寫入的順序筆數
     */
    public function rebuildPresetItems(int $cardSortId): int
    {
        $preset = \App\Models\DrawPreset::where('card_sort_id', $cardSortId)
            ->where('is_active', 1)
            ->first();

        DB::table('draw_preset_items')->where('card_sort_id', $cardSortId)->delete();

        if (empty($preset) || empty($preset->preset_data) || !is_array($preset->preset_data)) {
            return 0;
        }

        $rows = [];
        $seq = 0;
        $seen = [];
        foreach ($preset->preset_data as $pCard) {
            $cid = (int)($pCard['card_id'] ?? $pCard['product_id'] ?? 0);
            if ($cid <= 0 || isset($seen[$cid])) {
                continue;
            }
            $seen[$cid] = 1;
            $seq++;
            $rows[] = [
                'preset_id'    => (int)$preset->preset_id,
                'card_sort_id' => $cardSortId,
                'seq'          => $seq,
                'card_id'      => $cid
            ];
            // 分批寫入，避免單次 insert 過大
            if (count($rows) >= 500) {
                DB::table('draw_preset_items')->insert($rows);
                $rows = [];
            }
        }
        if (!empty($rows)) {
            DB::table('draw_preset_items')->insert($rows);
        }

        return $seq;
    }

    private function presetItemsExist(int $cardSortId): bool
    {
        return DB::table('draw_preset_items')->where('card_sort_id', $cardSortId)->exists();
    }

    /**
     * 單次抽取張數上限（app_settings.max_draw_count_per_order）
     * 0 或未配置 = 不限制，避免影響既有「一鍵包池」大額抽取
     */
    public static function maxDrawCountPerOrder(): int
    {
        try {
            if (self::hasTableCached('app_settings')) {
                $value = DB::table('app_settings')
                    ->where('key', 'max_draw_count_per_order')
                    ->value('value');
                return max(0, (int)$value);
            }
        } catch (\Throwable $e) {
            // 配置表異常時按不限制處理
        }
        return 0;
    }

    /**
     * 選卡：優先按預設順序（draw_preset_items 表）取在庫卡，不足再隨機補；無預設則純隨機
     * 順序表為正規化結構，一條 JOIN 即可取前 N 張，不再讀取/解析 MB 級 preset_data
     * @return array<int, array>
     */
    private function pickCardsLocked(int $cardSortId, int $count): array
    {
        $cardCols = ['card_id', 'card_sort_id', 'card_name', 'en_card_name', 'card_img', 'card_price', 'card_type', 'card_tag', 'card_stock'];

        $baseQuery = DB::table('card')
            ->where('card_status', 1)
            ->where('card_stock', '>', 0)
            ->where('card_sort_id', $cardSortId);

        // 順序表尚未建立時（功能上線前已有的預設），懶載入重建一次；
        // 加鎖避免並發首次抽卡時多個請求同時重建（重複 delete/insert 造成衝突）
        if (!$this->presetItemsExist($cardSortId)) {
            try {
                Cache::lock('draw:preset-rebuild:' . $cardSortId, 15)->block(5, function () use ($cardSortId) {
                    if (!$this->presetItemsExist($cardSortId)) {
                        $this->rebuildPresetItems($cardSortId);
                    }
                });
            } catch (\Throwable $e) {
                // 搶鎖失敗或快取不可用：直接嘗試重建
                if (!$this->presetItemsExist($cardSortId)) {
                    $this->rebuildPresetItems($cardSortId);
                }
            }
        }

        $cards = [];
        if ($this->presetItemsExist($cardSortId)) {
            // 按預設順序取前 N 張在庫卡（JOIN 順序表，走 idx_dpi_sort_seq）
            $rows = DB::table('draw_preset_items as i')
                ->join('card as c', 'c.card_id', '=', 'i.card_id')
                ->where('i.card_sort_id', $cardSortId)
                ->where('c.card_status', 1)
                ->where('c.card_stock', '>', 0)
                ->orderBy('i.seq')
                ->limit($count)
                ->get(array_map(function ($col) {
                    return 'c.' . $col;
                }, $cardCols));

            $pickedIds = [];
            foreach ($rows as $row) {
                $cards[] = (array)$row;
                $pickedIds[] = (int)$row->card_id;
            }

            // 預設內在庫卡不足時，隨機補足
            $need = $count - count($cards);
            if ($need > 0) {
                $extra = $baseQuery->clone()
                    ->whereNotIn('card_id', $pickedIds)
                    ->inRandomOrder()
                    ->limit($need)
                    ->get($cardCols);
                foreach ($extra as $row) {
                    $cards[] = (array)$row;
                }
            }
        } else {
            // 無預設：在 SQL 層直接隨機抽 N 張，避免全量撈回
            foreach ($baseQuery->clone()->inRandomOrder()->limit($count)->get($cardCols) as $row) {
                $cards[] = (array)$row;
            }
        }

        return $cards;
    }

    /**
     * 階段2：把 card_draw_tasks 中抽中的卡批量生成訂單（行鎖只作用於 task 行，不持卡鎖）
     * @return array{ok: bool, order_ids?: array, cards?: array, error?: string}
     */
    public function processDrawOrderTask(int $taskId): array
    {
        try {
            DB::beginTransaction();
            $task = DB::table('card_draw_tasks')->where('task_id', $taskId)->lockForUpdate()->first();
            if (empty($task)) {
                DB::rollBack();
                return ['ok' => false, 'error' => 'task not found'];
            }
            if ((int)$task->status === 1) {
                // 已被其他進程處理（冪等）
                DB::rollBack();
                return ['ok' => true, 'order_ids' => [], 'cards' => []];
            }

            $cards = json_decode($task->cards_json, true);
            if (!is_array($cards) || empty($cards)) {
                DB::rollBack();
                return ['ok' => false, 'error' => 'cards payload empty'];
            }

            $userId = (int)$task->user_id;
            $written = $this->writeCardOrders($cards, $userId, $taskId);
            $this->notifyRareDraw((int)$task->card_sort_id, $userId, $cards, $written['order_ids']);

            // 把建單後的 cards（含 card_order_details_id）寫回 cards_json
            // 這樣 getDrawTaskStatus 讀到的就是帶 details_id 的完整數據
            DB::table('card_draw_tasks')
                ->where('task_id', $taskId)
                ->update([
                    'status'      => 1,
                    'cards_json'  => json_encode($written['cards'], JSON_UNESCAPED_UNICODE),
                    'updated_at'  => date('Y-m-d H:i:s'),
                ]);
            DB::commit();

            return ['ok' => true, 'order_ids' => $written['order_ids'], 'cards' => $written['cards']];
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('processDrawOrderTask failed', ['task_id'=>$taskId, 'error'=>$e->getMessage()]);
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 批量寫入訂單/詳情/快照/物流（每張卡一筆獨立訂單）
     * @return array{order_ids: array, cards: array}
     */
    private function writeCardOrders(array $cards, int $userId, int $taskId = 0): array
    {
        $cardOrder = new CardOrder();
        $now = date('Y-m-d H:i:s');

        // 訂單上記錄抽卡批次號，供後台對賬精確關聯（欄位未遷移時自動跳過，避免影響抽卡）
        static $hasDrawTaskId = null;
        if ($hasDrawTaskId === null) {
            $hasDrawTaskId = self::hasColumnCached('order', 'draw_task_id');
        }

        // 構造全部訂單行一次 insert，再按 order_no 回讀 order_id 映射
        $plan = [];
        foreach ($cards as $item) {
            $plan[] = [
                'order_no' => $cardOrder->getOrderNumber(),
                'item'     => $item,
                'price'    => isset($item['card_price']) ? (float)$item['card_price'] : 0,
                'order_id' => 0
            ];
        }

        $orderRows = [];
        foreach ($plan as $p) {
            $row = [
                'order_no'        => $p['order_no'],
                'user_id'         => $userId,
                'coupon_id'       => 0,
                'original_cost'   => 0,
                'order_money'     => $p['price'],
                'freight_price'   => 0,
                'booking_price'   => 0,
                'coupon_money'    => 0,
                'payment_type'    => 1,
                'payment_mode'    => 4,
                'order_type'      => 1,
                'indent_type'     => 2,
                'receiving_type'  => 1,
                'delivery_area'   => '',
                'delivery_address'=> '',
                'delivery_name'   => '',
                'delivery_phone'  => '',
                'delivery_mail'   => '',
                'order_time'      => '',
                'order_num'       => '',
                'order_remark'    => '',
                'payment_status'  => 3,
                'payment_at'      => $now,
                'return_status'   => 0,
                'push_state'      => 1,
                'mail_state'      => 1,
                'order_status'    => 1,
                'created_at'      => $now,
                'updated_at'      => $now
            ];
            if ($hasDrawTaskId) {
                $row['draw_task_id'] = $taskId;
            }
            $orderRows[] = $row;
        }
        DB::table('order')->insert($orderRows);

        $orderNoMap = DB::table('order')
            ->whereIn('order_no', array_column($plan, 'order_no'))
            ->select('order_id', 'order_no')
            ->get()
            ->pluck('order_id', 'order_no')
            ->all();
        if (count($orderNoMap) !== count($plan)) {
            throw new \RuntimeException('create orders failed');
        }

        // 批量寫入訂單詳情
        $detailRows = [];
        foreach ($plan as $i => $p) {
            $item = $p['item'];
            $plan[$i]['order_id'] = (int)$orderNoMap[$p['order_no']];
            $detailRows[] = [
                'order_id'         => $plan[$i]['order_id'],
                'user_id'          => $userId,
                'product_id'       => $item['card_id'],
                'product_name'     => $item['card_name'],
                'en_product_name'  => $item['en_card_name'] ?? $item['card_name'],
                'product_img'      => $item['card_img'],
                'product_number'   => 1,
                'product_price'    => $p['price'],
                'product_money'    => $p['price'],
                'product_type'     => $item['card_type'],
                'logistics_status' => 0,
                'details_remark'   => '',
                'details_status'   => 1,
                'created_at'       => $now,
                'updated_at'       => $now
            ];
        }
        DB::table('order_details')->insert($detailRows);

        $detailIdMap = DB::table('order_details')
            ->whereIn('order_id', array_column($plan, 'order_id'))
            ->select('order_id', 'details_id')
            ->get()
            ->pluck('details_id', 'order_id')
            ->all();
        if (count($detailIdMap) !== count($plan)) {
            throw new \RuntimeException('create details failed');
        }

        // 批量寫入快照與物流；用戶快照只查一次，卡牌快照按卡緩存
        $userRow = User::where('user_id', $userId)->first();
        $userSnapshot = $userRow ? json_encode($userRow) : '{}';
        $orderIds = [];
        $allCards = [];
        $snapshotRows = [];
        $logisticsRows = [];
        $cardSnapshotCache = [];
        foreach ($plan as $p) {
            $oid = $p['order_id'];
            $orderIds[] = $oid;
            $cid = (int)$p['item']['card_id'];
            if (!array_key_exists($cid, $cardSnapshotCache)) {
                $cardSnapshotCache[$cid] = $this->CardSnapshot($cid);
            }
            $snapshotRows[] = [
                'details_id'       => (int)$detailIdMap[$oid],
                'coupon_snapshot'  => '',
                'product_snapshot' => $cardSnapshotCache[$cid],
                'user_snapshot'    => $userSnapshot,
                'snapshot_status'  => 1,
                'created_at'       => $now,
                'updated_at'       => $now
            ];
            $logisticsRows[] = [
                'user_id'         => $userId,
                'order_id'        => $oid,
                'logistics_status'=> 0,
                'created_at'      => $now,
                'updated_at'      => $now
            ];
            $cardOut = $p['item'];
            $cardOut['card_order_details_id'] = (int)$detailIdMap[$oid];
            $allCards[] = $cardOut;
        }
        DB::table('order_snapshot')->insert($snapshotRows);
        DB::table('order_logistics')->insert($logisticsRows);

        return ['order_ids' => $orderIds, 'cards' => $allCards];
    }

    /**
     * 抽中高价卡牌（card_price > 1500）時發送系統消息（失敗不影響主流程）
     */
    private function notifyRareDraw(int $cardSortId, int $userId, array $cards, array $orderIds): void
    {
        try {
            foreach ($cards as $dc) {
                $price = (float)($dc['card_price'] ?? 0);
                if ($price > 1500) {
                    $userName = DB::table('users')->where('user_id', $userId)->value('user_name') ?? '';
                    DB::table('system_info')->insert([
                        'user_id'            => $userId,
                        'system_type'        => 4,
                        'system_content'     => '恭喜会员:' . $userName . '獲得高價卡牌（¥' . $price . '）-' . ($dc['card_name'] ?? ''),
                        'en_system_content'  => 'Congratulations to ' . $userName . ' for getting a valuable card (¥' . $price . '): ' . ($dc['en_card_name'] ?? $dc['card_name'] ?? ''),
                        'order_id'           => $orderIds[0] ?? 0,
                        'is_read'            => 1,
                        'system_status'      => 1,
                        'created_at'         => date('Y-m-d H:i:s'),
                        'updated_at'         => date('Y-m-d H:i:s')
                    ]);
                    break;
                }
            }
        } catch (\Throwable $e) {
            // 通知失敗不影響主流程
        }
    }


    /**
     * 完全随机抽取指定数量的卡片（每张卡片只能被抽取一次）
     * 如果卡包有预设排序，则按预设顺序抽取
     *
     * @param array $cards 卡片数组
     * @param int $count 需要抽取的数量
     * @param int|null $CardSortId 卡包ID（用于预设排序）
     * @return array
     */
    public function drawCards(array $cards, int $count, $CardSortId = null){
        $count = (int)$count;
        if ($count <= 0) {
            return [];
        }
        $pool = $this->normalizeDrawPool($cards);
        if (empty($pool)) {
            return [];
        }

        if (!empty($CardSortId)) {
            $preset = \App\Models\DrawPreset::where('card_sort_id', $CardSortId)
                ->where('is_active', 1)
                ->first();

            if ($preset && !empty($preset->preset_data) && is_array($preset->preset_data)) {
                // 建立 pool 的 card_id => card 快速查詢映射
                $poolMap = [];
                foreach ($pool as $card) {
                    $poolMap[(string)$card['card_id']] = $card;
                }

                // 嚴格按照 preset_data 順序逐一選取可用卡片
                $result = [];
                $selectedIds = [];
                foreach ($preset->preset_data as $pCard) {
                    if (count($result) >= $count) {
                        break;
                    }
                    // 優先使用 card_id，向後相容 product_id
                    $pid = null;
                    if (isset($pCard['card_id'])) {
                        $pid = (string)$pCard['card_id'];
                    } elseif (isset($pCard['product_id'])) {
                        $pid = (string)$pCard['product_id'];
                    }
                    if ($pid === null) {
                        continue;
                    }
                    // 只在 pool 中存在且未被選取過時才選取
                    if (!isset($selectedIds[$pid]) && isset($poolMap[$pid])) {
                        $result[] = $poolMap[$pid];
                        $selectedIds[$pid] = true;
                    }
                }

                // 如果預設卡片不足，從剩餘 pool 中隨機補足
                if (count($result) < $count) {
                    $remaining = [];
                    foreach ($pool as $card) {
                        $cid = (string)$card['card_id'];
                        if (!isset($selectedIds[$cid])) {
                            $remaining[] = $card;
                        }
                    }
                    shuffle($remaining);
                    $needed = $count - count($result);
                    $result = array_merge($result, array_slice($remaining, 0, $needed));
                }

                return $result;
            } else {
                shuffle($pool);
            }
        } else {
            shuffle($pool);
        }

        return array_slice($pool, 0, min($count, count($pool)));
    }

    private function normalizeDrawPool(array $cards){
        $pool = [];
        foreach ($cards as $item) {
            if (!is_array($item)) {
                continue;
            }
            if ($this->isListArray($item) && isset($item[0]) && is_array($item[0])) {
                foreach ($item as $sub) {
                    if (is_array($sub)) {
                        $pool[] = $sub;
                    }
                }
                continue;
            }
            $pool[] = $item;
        }
        return array_values($pool);
    }

    private function isListArray(array $arr){
        return array_keys($arr) === range(0, count($arr) - 1);
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

    

    /**
     * 根據卡包ID（卡片分類ID）獲取卡片列表
     * @param $data
     * @return array
     */
    public function getCardsBySort($data){
        $where[] = array('card.card_status', '=', 1);
        $where[] = array('card.card_sort_id', '=', $data['card_sort_id']);
        $where[] = array('card.is_priority', '=', 1);

        // 獲取所有卡片，按標籤分組返回
        $allCards = $this->join('card_sort','card.card_sort_id','=','card_sort.card_sort_id')
            ->select($this->PrefixAppDetail)
            ->where($where)
            ->orderBy('card.created_at', 'desc')
            ->get()
            ->toArray();

        // 價格顯示：card_price 欄位名保持不變，但值改為 card_cost（正價/原價）
        foreach ($allCards as &$card) {
            $card['card_price'] = $card['card_cost'] ?? $card['card_price'];
        }
        unset($card);

        // 分組：按 card_tag 分組，null/空字串歸入「預設」
        $rawGrouped = [];
        foreach ($allCards as $card) {
            $tag = !empty($card['card_tag']) ? $card['card_tag'] : '預設';
            if (!isset($rawGrouped[$tag])) {
                $rawGrouped[$tag] = [];
            }
            $rawGrouped[$tag][] = $card;
        }

        $results['count'] = count($allCards);

        // 直接從 card_sort 表查詢抽卡價格、卡包圖片、動畫URL與名稱，不依賴卡片數據是否存在
        $hasOnceOnlyCol = false;
        try {
            $hasOnceOnlyCol = self::hasColumnCached('card_sort', 'is_once_only');
        } catch (\Throwable $e) {
            $hasOnceOnlyCol = false;
        }
        $sortCols = ['card_draw_price', 'card_sort_img', 'card_sort_url_single', 'card_sort_url_ten', 'card_sort_url_hundred', 'card_tag_order', 'card_tag_animations', $this->language.'card_sort_name as card_sort_name'];
        // draw_enable：關閉抽卡入口的卡池不返回給 APP 端
        $hasDrawEnableCol = false;
        try {
            $hasDrawEnableCol = self::hasColumnCached('card_sort', 'draw_enable');
        } catch (\Throwable $e) {
            $hasDrawEnableCol = false;
        }
        if ($hasDrawEnableCol) {
            $sortCols[] = 'draw_enable';
        }
        if ($hasOnceOnlyCol) {
            $sortCols[] = 'is_once_only';
        }
        $hasDrawLimitCol = false;
        try {
            $hasDrawLimitCol = self::hasColumnCached('card_sort', 'draw_limit_per_user');
        } catch (\Throwable $e) {
            $hasDrawLimitCol = false;
        }
        if ($hasDrawLimitCol) {
            $sortCols[] = 'draw_limit_per_user';
        }
        $hasDailyLimitCol = false;
        try {
            $hasDailyLimitCol = self::hasColumnCached('card_sort', 'daily_draw_limit');
        } catch (\Throwable $e) {
            $hasDailyLimitCol = false;
        }
        if ($hasDailyLimitCol) {
            $sortCols[] = 'daily_draw_limit';
        }
        $hasMemberLimitCol = false;
        try {
            $hasMemberLimitCol = self::hasColumnCached('card_sort', 'member_level_limit');
        } catch (\Throwable $e) {
            $hasMemberLimitCol = false;
        }
        if ($hasMemberLimitCol) {
            $sortCols[] = 'member_level_limit';
        }
        $hasMemberLimitsCol = false;
        try {
            $hasMemberLimitsCol = self::hasColumnCached('card_sort', 'member_level_limits');
        } catch (\Throwable $e) {
            $hasMemberLimitsCol = false;
        }
        if ($hasMemberLimitsCol) {
            $sortCols[] = 'member_level_limits';
        }
        $cardSortInfo = DB::table('card_sort')
            ->where('card_sort_id', $data['card_sort_id'])
            ->select($sortCols)
            ->first();
        // 關閉抽卡入口的卡池（draw_enable = 0）不返回給 APP 端，避免用戶通過深鏈/緩存進入已關閉的卡池
        if ($hasDrawEnableCol && $cardSortInfo && (int)($cardSortInfo->draw_enable ?? 1) === 0) {
            return $this->error(__('api.draw_disabled'));
        }
        $results['card_draw_price'] = $cardSortInfo && $cardSortInfo->card_draw_price !== null ? (float)$cardSortInfo->card_draw_price : 0;
        $results['card_sort_img'] = $cardSortInfo ? $cardSortInfo->card_sort_img : '';
        $results['card_sort_url_single'] = $cardSortInfo ? $cardSortInfo->card_sort_url_single : '';
        $results['card_sort_url_ten'] = $cardSortInfo ? $cardSortInfo->card_sort_url_ten : '';
        $results['card_sort_url_hundred'] = $cardSortInfo ? $cardSortInfo->card_sort_url_hundred : '';
        $results['card_sort_name'] = $cardSortInfo ? $cardSortInfo->card_sort_name : '';
        // 標籤順序（獎勵大到小，已過濾「預設」）與每個標籤單獨的中獎動畫（未設置的標籤使用默認動畫 card_sort_url_single/ten/hundred）
        $tagOrder = $cardSortInfo && !empty($cardSortInfo->card_tag_order) ? json_decode($cardSortInfo->card_tag_order, true) : null;
        $tagOrder = $this->filterDefaultTagFromOrder($tagOrder);
        $results['card_tag_order'] = !empty($tagOrder) ? $tagOrder : null;
        $tagAnimations = $cardSortInfo && !empty($cardSortInfo->card_tag_animations) ? json_decode($cardSortInfo->card_tag_animations, true) : null;
        $results['card_tag_animations'] = is_array($tagAnimations) ? $tagAnimations : [];
        // 優惠卡包：App 端據此只顯示「抽一次」按鈕
        $isOnceOnly = $hasOnceOnlyCol && $cardSortInfo && isset($cardSortInfo->is_once_only) && (int)$cardSortInfo->is_once_only === 1;
        $results['is_once_only'] = $isOnceOnly ? 1 : 0;
        // 會員等級限制：多等級（member_level_limits JSON）+ 每等級可抽張數，兼容舊單等級字段
        // 返回給 APP 的等級列表含名稱、積分範圍、該等級可抽張數；member_level_ok 表示當前用戶檔位是否命中
        $levelLimits = CardSort::resolveMemberLevelLimits(
            $hasMemberLimitsCol ? ($cardSortInfo->member_level_limits ?? null) : null,
            $hasMemberLimitCol ? ($cardSortInfo->member_level_limit ?? 0) : 0
        );
        $levelDisplay = [];
        $levelOk = 1;
        $levelLimit = 0;
        $levelCfg = null;
        if (!empty($levelLimits)) {
            $memberInfoMap = UserMember::memberInfoByIds(array_column($levelLimits, 'member_id'), $this->language);
            $userEnd = 0;
            $authUserForLevel = auth('api')->user();
            if (!empty($authUserForLevel) || !empty($data['user_id'])) {
                $uid = !empty($authUserForLevel) ? $authUserForLevel->user_id : $data['user_id'];
                $userIntegral = (int)DB::table('users')->where('user_id', $uid)->value('user_integral_all');
                $userEnd = UserMember::memberEndByScore($userIntegral);
            }
            foreach ($levelLimits as $levelItem) {
                $info = $memberInfoMap[(int)$levelItem['member_id']] ?? null;
                $levelDisplay[] = [
                    'member_id'   => (int)$levelItem['member_id'],
                    'name'        => $info ? $info['name'] : ('ID:' . $levelItem['member_id']),
                    'score_range' => $info ? [(int)$info['member_start'], (int)$info['member_end']] : null,
                    'draw_limit'  => (int)$levelItem['draw_limit'],
                ];
            }
            $levelCfg = CardSort::matchMemberLevelLimit($levelLimits, $memberInfoMap, $userEnd);
            $levelOk = $levelCfg ? 1 : 0;
            $levelLimit = $levelCfg ? max(0, (int)$levelCfg['draw_limit']) : 0;
        }
        $results['member_level_limits'] = $levelDisplay;
        $results['member_level_limit'] = !empty($levelDisplay) ? $levelDisplay[0]['member_id'] : 0;
        $results['member_level_limit_name'] = !empty($levelDisplay)
            ? implode('/', array_column($levelDisplay, 'name')) : null;
        $results['member_level_limit_score_range'] = count($levelDisplay) === 1
            ? $levelDisplay[0]['score_range'] : null;
        $results['member_level_ok'] = $levelOk;
        $results['member_level_draw_limit'] = $levelLimit;

        // 每用戶抽取張數上限 + 剩餘張數，口徑與 confirmDrawOrder 保持一致（按卡牌張數統計）：
        // 全局上限與會員等級上限同時生效時取較小值；否則優惠卡包 -> user_promo_draw(上限1)；其餘無限制(-1)
        $sortLimit = $hasDrawLimitCol && $cardSortInfo ? max(0, (int)($cardSortInfo->draw_limit_per_user ?? 0)) : 0;
        $results['draw_limit_per_user'] = $sortLimit;
        $effectiveLimits = array_values(array_filter([$sortLimit, $levelLimit], function ($v) {
            return $v > 0;
        }));
        $effectiveLimit = empty($effectiveLimits) ? 0 : min($effectiveLimits);
        $results['effective_draw_limit'] = $effectiveLimit;
        $results['draw_count']     = 0;
        $results['user_drawn']     = 0;
        $results['draw_remaining'] = $levelOk ? -1 : 0;
        // 每日抽取張數限制 + 今日已抽/剩餘（0 = 不限制；與總張數限制同時生效，每日0點重置）
        $dailyLimit = $hasDailyLimitCol && $cardSortInfo ? max(0, (int)($cardSortInfo->daily_draw_limit ?? 0)) : 0;
        $results['daily_draw_limit']     = $dailyLimit;
        $results['draw_today']           = 0;
        $results['draw_today_remaining'] = -1;
        try {
            $authUser = auth('api')->user();
            if (!empty($authUser)) {
                if ($dailyLimit > 0 && self::hasTableCached('user_daily_draw_count')) {
                    $todayUsed = (int)DB::table('user_daily_draw_count')
                        ->where('user_id', $authUser->user_id)
                        ->where('card_sort_id', $data['card_sort_id'])
                        ->whereDate('draw_date', date('Y-m-d'))
                        ->value('draw_count');
                    $results['draw_today']           = $todayUsed;
                    $results['draw_today_remaining'] = max(0, $dailyLimit - $todayUsed);
                }
                if (!$levelOk) {
                    $results['draw_count'] = 0;
                    $results['user_drawn'] = 0;
                    $results['draw_remaining'] = 0;
                } elseif ($effectiveLimit > 0 && self::hasTableCached('user_draw_count')) {
                    $used = (int)DB::table('user_draw_count')
                        ->where('user_id', $authUser->user_id)
                        ->where('card_sort_id', $data['card_sort_id'])
                        ->value('draw_count');
                    $results['draw_count'] = $used;
                    $results['user_drawn'] = $used > 0 ? 1 : 0;
                    $results['draw_remaining'] = max(0, $effectiveLimit - $used);
                } elseif ($effectiveLimit <= 0 && $isOnceOnly && self::hasTableCached('user_promo_draw')) {
                    $used = DB::table('user_promo_draw')
                        ->where('user_id', $authUser->user_id)
                        ->where('card_sort_id', $data['card_sort_id'])
                        ->exists() ? 1 : 0;
                    $results['draw_count'] = $used;
                    $results['user_drawn'] = $used;
                    $results['draw_remaining'] = 1 - $used;
                }
            }
        } catch (\Throwable $e) {
            // 保留預設值
        }

        // 統計所有標籤
        $allTags = Card::where('card_sort_id', $data['card_sort_id'])
            ->where('card_status', 1)
            ->whereNotNull('card_tag')
            ->distinct()
            ->pluck('card_tag')
            ->toArray();

        // 讀取後台保存的標籤順序
        $savedOrder = null;
        $cardSortInfoForTag = \Illuminate\Support\Facades\DB::table('card_sort')
            ->where('card_sort_id', $data['card_sort_id'])
            ->value('card_tag_order');
        if ($cardSortInfoForTag) {
            $savedOrder = json_decode($cardSortInfoForTag, true);
        }

        // 確保「預設」永遠在第一個位置
        $tags = array_values(array_filter($allTags, function($t) {
            return $t !== '預設';
        }));

        if (!empty($savedOrder) && is_array($savedOrder)) {
            // 按後台保存順序排列
            $orderedTags = array_values(array_intersect($savedOrder, $tags));
            // 補上未在 savedOrder 中的標籤
            $missingTags = array_diff($tags, $savedOrder);
            sort($missingTags);
            $tags = array_merge($orderedTags, $missingTags);
        } else {
            sort($tags);
        }
        // 確保「預設」永遠在第一個位置
        $defaultIdx = array_search('預設', $tags);
        if ($defaultIdx !== false && $defaultIdx > 0) {
            array_splice($tags, $defaultIdx, 1);
            array_unshift($tags, '預設');
        }
        $results['tags'] = $tags;

        // 按 tags 順序重建 data 分組
        $orderedData = [];
        foreach ($tags as $tag) {
            if (isset($rawGrouped[$tag])) {
                $orderedData[$tag] = $rawGrouped[$tag];
            }
        }
        $results['data'] = $orderedData;

        // 統計每個標籤的總卡牌數和未抽取數
        // 先查詢 pending draw_session 中的卡片，從 undrawn 中扣除
        $pendingCardIds = DB::table('draw_session')
            ->where('status', 0)
            ->where('expires_at', '>', now())
            ->where('card_sort_id', $data['card_sort_id'])
            ->get(['draw_data'])
            ->flatMap(function ($session) {
                $cards = json_decode($session->draw_data, true);
                return $cards ? array_column($cards, 'card_id') : [];
            })
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $pendingCardTags = [];
        $pendingCount = count($pendingCardIds);
        if (!empty($pendingCardIds)) {
            $pendingCards = Card::whereIn('card_id', $pendingCardIds)
                ->where('card_sort_id', $data['card_sort_id'])
                ->where('card_status', 1)
                ->select('card_id', 'card_tag')
                ->get();
            foreach ($pendingCards as $pc) {
                $tag = !empty($pc->card_tag) ? $pc->card_tag : '預設';
                if (!isset($pendingCardTags[$tag])) {
                    $pendingCardTags[$tag] = 0;
                }
                $pendingCardTags[$tag]++;
            }
        }

        $tagStats = [];
        foreach ($tags as $tag) {
            $baseQuery = Card::where('card_sort_id', $data['card_sort_id'])
                ->where('card_status', 1)
                ->where(function($q) use ($tag) {
                    if ($tag === '預設') {
                        $q->whereNull('card_tag')->orWhere('card_tag', '');
                    } else {
                        $q->where('card_tag', $tag);
                    }
                });
            $total = (clone $baseQuery)->count();
            $undrawn = (clone $baseQuery)->where('card_stock', '>', 0)->count();
            // 扣除該標籤下 pending 中的卡片
            $tagPending = $pendingCardTags[$tag] ?? 0;
            $tagStats[$tag] = [
                'total' => $total,
                'undrawn' => max(0, $undrawn - $tagPending),
            ];
        }
        $results['tag_stats'] = $tagStats;

        // 整個卡池統計（排除 pending draw_session 中的卡片）
        $totalRemaining = Card::where('card_sort_id', $data['card_sort_id'])
            ->where('card_status', 1)
            ->where('card_stock', '>', 0)
            ->count();
        $totalDrawn = Card::where('card_sort_id', $data['card_sort_id'])
            ->where('card_status', 1)
            ->where('card_stock', '<', 1)
            ->count();

        $results['total_remaining'] = max(0, $totalRemaining - $pendingCount);
        $results['total_drawn'] = $totalDrawn + $pendingCount;

        // 整個卡池總庫存（所有上架卡片 card_stock 之和，與後台「卡池管理」的 sum(card_stock) 同口徑）
        $results['total_stock'] = max(0, (int) Card::where('card_sort_id', $data['card_sort_id'])
            ->where('card_status', 1)
            ->sum('card_stock'));

        // 本次單次可選的最大張數：卡池剩餘 / 每用戶剩餘 / 今日剩餘 / 優惠卡包 / 單次上限 取最小值
        // 各剩餘字段：-1 = 不限制；會員等級不符或無剩餘時為 0
        $maxDrawCount = (int)$results['total_remaining'];
        if ((int)$results['draw_remaining'] > 0) {
            $maxDrawCount = min($maxDrawCount, (int)$results['draw_remaining']);
        }
        if ((int)$results['draw_today_remaining'] > 0) {
            $maxDrawCount = min($maxDrawCount, (int)$results['draw_today_remaining']);
        }
        if ($isOnceOnly) {
            $maxDrawCount = min($maxDrawCount, 1);
        }
        $maxPerOrder = self::maxDrawCountPerOrder();
        if ($maxPerOrder > 0) {
            $maxDrawCount = min($maxDrawCount, $maxPerOrder);
        }
        if ((int)$results['member_level_ok'] === 0) {
            $maxDrawCount = 0;
        }
        $results['max_draw_count'] = max(0, $maxDrawCount);

        // 額外獎勵規則（每 N 張發一張獎勵卡）：單池版本
        $bonusRules = [];
        $sortDrawnTotal = 0;
        try {
            $hasBonusRuleTable = DB::getSchemaBuilder()->hasTable('card_sort_bonus_rule');
            $hasDrawnTotalCol = self::hasColumnCached('card_sort', 'drawn_total');
            if ($hasBonusRuleTable) {
                $bRows = DB::table('card_sort_bonus_rule')
                    ->where('card_sort_id', $data['card_sort_id'])
                    ->where('is_active', 1)
                    ->orderBy('trigger_at', 'asc')
                    ->get(['trigger_at', 'card_id']);
                $bCardIds = array_values(array_unique(array_filter(array_map('intval', $bRows->pluck('card_id')->toArray()))));
                $bCardInfo = [];
                if (!empty($bCardIds)) {
                    $bCardInfo = DB::table('card')
                        ->whereIn('card_id', $bCardIds)
                        ->where('card_status', 1)
                        ->get(['card_id', 'card_name', 'card_img', 'card_tag'])
                        ->keyBy('card_id')
                        ->toArray();
                }
                foreach ($bRows as $br) {
                    $info = $bCardInfo[$br->card_id] ?? null;
                    $bonusRules[] = [
                        'trigger_at' => (int)$br->trigger_at,
                        'card_id'    => (int)$br->card_id,
                        'card_name'  => $info ? ($info->card_name ?? '') : '',
                        'card_img'   => $info ? ($info->card_img ?? '') : '',
                        'card_tag'   => $info ? ($info->card_tag ?? '') : '',
                    ];
                }
            }
            if ($hasDrawnTotalCol) {
                $sortDrawnTotal = (int)DB::table('card_sort')
                    ->where('card_sort_id', $data['card_sort_id'])
                    ->value('drawn_total');
            }
        } catch (\Throwable $e) {
            // 新表/新列不存在時跳過，不影響接口正常返回
        }
        $results['bonus_rules'] = $bonusRules;
        $results['drawn_total'] = $sortDrawnTotal;

        if(!empty($results['data'])){
            return $this->success(__('api.list_success'), $results);
        }else{
            return $this->success(__('api.list_null'), $results);
        }
    }













/**************************************************common(api和admin共用)***********************************************************/

    /**
     * @param $data
     * @return array
     * 獲取卡片详情
     */
    public function CommonCardDetails($data){
        $where[] = array('card.card_id','=',$data['card_id']);
        $where[] = array('card.card_status','=',1);
        $effects = $this->join('card_sort','card.card_sort_id','=','card_sort.card_sort_id')
            ->select($this->PrefixAppDetail,'card_sort.'.$this->language .'card_sort_name as card_sort_name')
            ->where($where)->first()->toArray();
        //是否收藏
        if (!empty($data['user_id'])) {
            $collect = $this->from('users as u')->join('user_collect as c', 'c.user_id', '=', 'u.user_id')
                            ->where('u.user_status', '=', 1)->where('u.user_id', '=', $data['user_id'])->where('c.card_id', '=', $effects['card_id'])
                            ->first();
            if (empty($collect)) {
                $effects['user_collect'] = 0;
            } else {
                $effects['user_collect'] = 1;
            }
            } else {
                $effects['user_collect'] = 0;
            }
        if(!empty($effects['card_relation'])){
            foreach ($effects['card_relation'] as $k => $v) {
                $effects['card_relation'][$k]['card_specs_value'] = [];
                $effects['card_relation'][$k]['card_specs_value'] = CardSpecsValue::whereIn('card_specs_value_id',explode('-',$effects['card_relation'][$k]['card_specs_value_id']))->get()->toArray();
            }
        }
        return $effects;
    }



    /**
     * @param $data
     * @return array
     * 獲取卡片詳情規格
     */
    public function CommonCardSpecs($data){
        $where[] = array('card.card_id','=',$data['card_id']);
        $where[] = array('card.card_status','=',1);
        $effects = $this->join('card_sort','card.card_sort_id','=','card_sort.card_sort_id')
            ->select($this->PrefixAppDetail,'card_sort.'.$this->language .'card_sort_name as card_sort_name')
            ->where($where)->first()->toArray();
        //是否收藏
        if (!empty($data['user_id'])) {
            $collect = $this->from('users as u')->join('user_collect as c', 'c.user_id', '=', 'u.user_id')
                            ->where('u.user_status', '=', 1)->where('u.user_id', '=', $data['user_id'])->where('c.card_id', '=', $effects['card_id'])
                            ->first();
            if (empty($collect)) {
                $effects['user_collect'] = 0;
            } else {
                $effects['user_collect'] = 1;
            }
            } else {
                $effects['user_collect'] = 0;
            }
        if(!empty($effects['card_relation'])){
            foreach ($effects['card_relation'] as $k => $v) {
                $effects['card_relation'][$k]['card_specs_value'] = [];
                $effects['card_relation'][$k]['card_specs_value'] = CardSpecsValue::whereIn('card_specs_value_id',explode('-',$effects['card_relation'][$k]['card_specs_value_id']))->get()->toArray();
            }
        }
        return $effects;
    }



    /**
     * @param $data
     * @return array
     * 獲取卡片快照
     */
    public function CardSnapshot($card_id){
        $where[] = array('card.card_id','=',$card_id);
        $where[] = array('card.card_status','=',1);
        $effects = $this->join('card_sort','card.card_sort_id','=','card_sort.card_sort_id')
            // ->select($this->PrefixAppDetail,'card_sort.'.$this->language .'card_sort_name as card_sort_name')
        ->select('*') ->where($where)->first()->toArray();
        if(!empty($effects['card_relation'])){
            foreach ($effects['card_relation'] as $k => $v) {
                $effects['card_relation'][$k]['card_specs_value'] = [];
                $effects['card_relation'][$k]['card_specs_value'] = CardSpecsValue::whereIn('card_specs_value_id',explode('-',$effects['card_relation'][$k]['card_specs_value_id']))->get()->toArray();
            }
        }
        //轉json
        return  json_encode($effects);
    }



    /**
     * 獲取卡片的抽取人信息
     * @param $card_id
     * @return array
     */
    public function getCardDrawerInfo($card_id){
        $detail = DB::table('order_details')
            ->join('users', 'order_details.user_id', '=', 'users.user_id')
            ->leftJoin('order', 'order_details.order_id', '=', 'order.order_id')
            ->where('order_details.product_id', $card_id)
            ->where('order_details.details_status', '<>', -1)
            ->orderBy('order_details.created_at', 'desc')
            ->select(
                'users.user_name',
                'users.user_phone',
                'users.user_avatar',
                'users.user_email',
                'order_details.created_at as drawn_at',
                'order.order_no'
            )
            ->first();

        if ($detail) {
            return [
                'user_name'   => $detail->user_name ?? '',
                'user_phone'  => $detail->user_phone ?? '',
                'user_avatar' => $detail->user_avatar ?? '',
                'user_email'  => $detail->user_email ?? '',
                'drawn_at'    => $detail->drawn_at ?? '',
                'order_num'   => $detail->order_no ?? ''
            ];
        }
        return null;
    }


/**************************************************END***********************************************************/












}
