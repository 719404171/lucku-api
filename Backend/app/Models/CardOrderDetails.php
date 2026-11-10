<?php
/*
 * File: CardOrderDetails.php
 * Project: NewDemo
 * Created Time: 2026-06-04 04:11:11 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2026-07-02 4:12:44 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2026 成都睿創科技有限公司
 */

namespace App\Models;

use App\Http\Controllers\Api\MailController;
use App\Http\Controllers\Controller;
use DateTimeInterface;
use App\Models\Base;
use App\Models\Card;
use App\Models\User;
use App\Models\Order;
use App\Models\CardOrder;
use App\Models\SystemInfo;
use App\Models\UserWalletRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;

class CardOrderDetails extends Base{

    protected $table = 'order_details';
    protected $primaryKey = 'details_id';
    use Notifiable;
    public $timestamps = true;
    public static $modify = false;
    protected $appends = ['card_order_details_id'];

    public function getCardOrderDetailsIdAttribute(){
        return $this->attributes['details_id'] ?? 0;
    }

    protected function serializeDate(DateTimeInterface $date){
        return $date->format($this->dateFormat ?: 'Y-m-d H:i:s');
    }

    public function CardOrderLogistics(){
        return $this->hasMany('App\Models\CardOrderLogistics', 'order_id', 'order_id');
    }

    public function OrderSnapshot(){
        return $this->hasMany('App\Models\CardOrderSnapshot', 'details_id', 'details_id');
    }

    public function product(){
        return $this->belongsTo(Card::class, 'product_id');
    }

    public function __construct(){
        $this->language = app('global_language') == "en" ? "en_" : "";
        $this->AppDetail = ['details_id',$this->language.'product_name as card_name','product_img','order_id','product_number as card_number','product_price as card_price','product_money as card_money','created_at'];
    }

    protected static function boot(){
       parent::boot();
       static::addGlobalScope('order_details', function (Builder $builder) {
          return $builder->where('order_details.details_status', '<>', -1);
       });
    }

    /**
     * 獲取用戶背包（訂單詳情列表）
     */
    public function getUserBackpack($data){
        $limit = empty($data['limit']) ? 10 : $data['limit'];
        $page = ( $data['page'] - 1 ) * $limit;

        $where[] = array('order_details.user_id', '=', $data['user_id']);
        $where[] = array('order_details.details_status', '<>', -1);

        // 按訂單類型過濾：
        // APP 傳 indent_type=0 表示「全部」（產品+抽卡都顯示在背包裡讓用戶選）
        // APP 傳 indent_type=1 只顯示產品
        // APP 傳 indent_type=2 只顯示抽卡
        // 沒傳的話默認全部（背包本來就是讓用戶選所有可發貨的）
        $indentType = isset($data['indent_type']) ? (int)$data['indent_type'] : 0;
        if ($indentType === 1) {
            $where[] = array('order.indent_type', '=', 1);
        } elseif ($indentType === 2) {
            $where[] = array('order.indent_type', '=', 2);
        }
        // indent_type=0 或沒傳 → 不加條件，顯示 1+2（全部）

        // 只有沒傳 pack_status 才走默認隱藏已處理
        // 注意：exchange_status 只對抽卡訂單(indent_type=2)有意義
        // 產品訂單(indent_type=1)沒 exchange_status 狀態，不能用這個條件擋
        $applyExchangeFilter = false;
        $exchangeMapStatus = null;
        if (isset($data['pack_status']) && in_array((int)$data['pack_status'], [1, 2, 3])) {
            $applyExchangeFilter = true;
            $exchangeMapStatus = [1 => 0, 2 => 1, 3 => 2][(int)$data['pack_status']];
        }

        $query = $this->join('order', 'order_details.order_id', '=', 'order.order_id')
            ->leftJoin('card', 'order_details.product_id', '=', 'card.card_id')
            ->where($where);

        // 在 $query 創建後加狀態過濾（因需要 OR 邏輯）
        if ($applyExchangeFilter) {
            // 用戶明確指定了 pack_status，對抽卡篩 exchange_status，對產品篩 logistics_status
            // pack_status=1(未處理) → exchange_status=0 / logistics_status=0
            // pack_status=2(已兌換) → exchange_status=1（產品沒這個狀態）
            // pack_status=3(已發貨) → exchange_status=2 / logistics_status=1
            $packStatus = (int)$data['pack_status'];
            $query->where(function ($q) use ($packStatus) {
                if ($packStatus === 2) {
                    // 已兌換：只有抽卡有這個狀態，產品全部排除
                    $q->where('order.indent_type', '=', 2)
                      ->where('order_details.exchange_status', '=', 1);
                } else {
                    // 未處理或已發貨：抽卡用 exchange_status，產品用 logistics_status
                    $cardEs = $packStatus === 1 ? 0 : 2;
                    $productLs = $packStatus === 1 ? 0 : 1;
                    $q->where(function ($q2) use ($cardEs, $productLs) {
                        $q2->where('order.indent_type', '=', 1)
                           ->where('order_details.logistics_status', '=', $productLs);
                    })->orWhere(function ($q2) use ($cardEs) {
                        $q2->where('order.indent_type', '=', 2)
                           ->where('order_details.exchange_status', '=', $cardEs);
                    });
                }
            });
        } elseif (empty($data['show_all'])) {
            // 默認隱藏已處理/已發貨的
            // 產品：logistics_status=0 未發貨才顯示
            // 抽卡：exchange_status=0 未處理才顯示
            $query->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->where('order.indent_type', '=', 1)
                       ->where('order_details.logistics_status', '=', 0);
                })->orWhere(function ($q2) {
                    $q2->where('order.indent_type', '=', 2)
                       ->where('order_details.exchange_status', '=', 0);
                });
            });
        }

        $count = (clone $query)->count();

        $list = $query->select(
                'order_details.*',
                'order.order_no as card_order_no',
                'order.created_at as order_created_at',
                'order.indent_type',
                'card.card_tag',
                'card.deal_condition'
            )
            // 價格排序可選：price_sort=asc/desc 按 product_money；沒傳就保持默認的創建時間倒序
            // 無論哪種排序都加上 details_id desc 作為穩定次級排序，避免同秒抽卡記錄在分頁時重複/漏項
            ->when(!empty($data['price_sort']) && in_array($data['price_sort'], ['asc', 'desc'], true),
                function ($q) use ($data) {
                    $q->orderBy('order_details.product_money', $data['price_sort'])
                      ->orderBy('order_details.created_at', 'desc')
                      ->orderBy('order_details.details_id', 'desc');
                },
                function ($q) {
                    $q->orderBy('order_details.created_at', 'desc')
                      ->orderBy('order_details.details_id', 'desc');
                }
            )
            ->offset($page)->limit($limit)
            ->get()
            ->toArray();

        $lastPage = ceil($count / $limit);
        $results = [
            'count' => $count,
            'currentPage' => (int)$data['page'],
            'limit' => (int)$limit,
            'lastPage' => (int)$lastPage,
            'data' => $list
        ];

        if (!empty($list)) {
            return $this->success(__('api.list_success'), $results);
        } else {
            return $this->success(__('api.list_null'), $results);
        }
    }

    /**
     * 後台-抽卡明細列表（每張抽出的卡一行）
     * 抽卡訂單標識：order.indent_type = 2（與 getUserBackpack 保持一致）
     */
    public function getDrawDetailListWeb($data, $language = ''){
        $limit = empty($data['limit']) ? 10 : $data['limit'];
        $page = ($data['page'] - 1) * $limit;
        $time = [];

        $where[] = array('order.indent_type', '=', 2);
        $where[] = array('order.order_status', '<>', -1);
        if (!empty($data['user_id'])) {
            $where[] = array('order_details.user_id', '=', (int)$data['user_id']);
        }
        if (!empty($data['user_name'])) {
            $where[] = array('users.user_name', 'like', '%' . $data['user_name'] . '%');
        }
        if (!empty($data['user_email'])) {
            $where[] = array('users.user_email', 'like', '%' . $data['user_email'] . '%');
        }
        if (!empty($data['order_no'])) {
            $where[] = array('order.order_no', 'like', '%' . $data['order_no'] . '%');
        }
        if (!empty($data['card_name'])) {
            $where[] = array('order_details.product_name', 'like', '%' . $data['card_name'] . '%');
        }
        if (!empty($data['card_sort_id'])) {
            $where[] = array('card.card_sort_id', '=', (int)$data['card_sort_id']);
        }
        if (!empty($data['time'])) {
            $time['start_time'] = $data['time'][0];
            $time['end_time'] = $data['time'][1];
        }

        // 每次調用獨立構造查詢，避免 data / count 共用同一 builder 互相污染
        $build = function () use ($where, $time) {
            return $this->join('order', 'order_details.order_id', '=', 'order.order_id')
                ->join('users', 'users.user_id', '=', 'order_details.user_id')
                ->leftJoin('card', 'order_details.product_id', '=', 'card.card_id')
                ->leftJoin('card_sort', 'card.card_sort_id', '=', 'card_sort.card_sort_id')
                ->where($where)
                ->where(function ($query) use ($time) {
                    if (!empty($time) && $time['start_time'] != '') {
                        return $query->whereBetween('order_details.created_at', $time);
                    }
                });
        };

        $datas['data'] = $build()
            ->select(
                'order_details.details_id',
                'order_details.order_id',
                'order_details.user_id',
                'order_details.product_id',
                'order_details.product_name',
                'order_details.en_product_name',
                'order_details.product_img',
                'order_details.product_price',
                'order_details.product_money',
                'order_details.product_type',
                'order_details.created_at',
                'order.order_no',
                'order.payment_at',
                'users.user_name',
                'users.user_email',
                'users.user_phone',
                'card.card_sort_id',
                'card_sort.' . $language . 'card_sort_name as card_sort_name'
            )
            ->orderBy('order_details.details_id', 'desc')
            ->offset($page)->limit($limit)
            ->get()->toArray();

        $datas['count'] = $build()->count();
        return $datas;
    }

    /**
     * 後台-卡牌多用戶抽取檢測列表
     * 以卡牌（order_details.product_id）分組，篩出被 >= 2 個不同用戶抽到的卡牌。
     * 支持卡牌ID/名稱、用戶ID/暱稱/郵箱、時間範圍多維度檢索。
     * 預警：1=多用戶抽取（橙） 2=超賣（庫存為負，紅）
     * @param array $data
     * @param string $language
     * @return array
     */
    public function getMultiDrawCheckListWeb($data, $language = ''){
        $limit = empty($data['limit']) ? 10 : (int)$data['limit'];
        $limit = max(1, min($limit, 5000));
        $page = max(1, (int)($data['page'] ?? 1));
        $offset = ($page - 1) * $limit;

        // 分組基礎查詢：抽卡訂單（indent_type=2）逐張卡
        $build = function () use ($data) {
            $query = DB::table('order_details as od')
                ->join('order as o', 'od.order_id', '=', 'o.order_id')
                ->where('o.indent_type', 2)
                ->where('o.order_status', '<>', -1)
                ->where('od.details_status', '<>', -1)
                // 屏蔽 2026-07-17 深夜脚本批量抽卡测试数据
                ->whereNotBetween('od.created_at', ['2026-07-17 23:00:00', '2026-07-18 01:00:00']);

            if (!empty($data['card_id'])) {
                $query->where('od.product_id', (int)$data['card_id']);
            }
            if (!empty($data['card_name'])) {
                $query->where('od.product_name', 'like', '%' . $data['card_name'] . '%');
            }
            if (!empty($data['card_sort_id'])) {
                $query->whereExists(function ($sub) use ($data) {
                    $sub->select(DB::raw(1))
                        ->from('card as c')
                        ->whereColumn('c.card_id', 'od.product_id')
                        ->where('c.card_sort_id', (int)$data['card_sort_id']);
                });
            }
            if (!empty($data['time']) && !empty($data['time'][0])) {
                $query->whereBetween('od.created_at', [$data['time'][0], $data['time'][1]]);
            }

            // 用戶篩選：僅保留該用戶參與過的卡牌，不改變分組統計口徑（仍統計全部用戶）
            $userId = !empty($data['user_id']) ? (int)$data['user_id'] : 0;
            $userName = !empty($data['user_name']) ? trim($data['user_name']) : '';
            $userEmail = !empty($data['user_email']) ? trim($data['user_email']) : '';
            if ($userId > 0 || $userName !== '' || $userEmail !== '') {
                $query->whereExists(function ($sub) use ($userId, $userName, $userEmail) {
                    $sub->select(DB::raw(1))
                        ->from('order_details as od2')
                        ->join('order as o2', 'od2.order_id', '=', 'o2.order_id')
                        ->join('users as u2', 'od2.user_id', '=', 'u2.user_id')
                        ->whereColumn('od2.product_id', 'od.product_id')
                        ->where('o2.indent_type', 2)
                        ->where('o2.order_status', '<>', -1)
                        ->where('od2.details_status', '<>', -1);
                    if ($userId > 0) {
                        $sub->where('od2.user_id', $userId);
                    }
                    if ($userName !== '') {
                        $sub->where('u2.user_name', 'like', '%' . $userName . '%');
                    }
                    if ($userEmail !== '') {
                        $sub->where('u2.user_email', 'like', '%' . $userEmail . '%');
                    }
                });
            }

            return $query->groupBy('od.product_id')
                ->havingRaw('COUNT(DISTINCT od.user_id) >= 2')
                ->select(
                    'od.product_id as card_id',
                    DB::raw('COUNT(*) as draw_count'),
                    DB::raw('COUNT(DISTINCT od.user_id) as user_count'),
                    DB::raw('MIN(od.created_at) as first_draw_at'),
                    DB::raw('MAX(od.created_at) as last_draw_at'),
                    DB::raw('MAX(od.product_name) as product_name'),
                    DB::raw('MAX(od.en_product_name) as en_product_name')
                );
        };

        $datas['count'] = DB::query()->fromSub($build(), 't')->count();

        $rows = $build()
            ->orderByRaw('user_count DESC, draw_count DESC, first_draw_at ASC')
            ->offset($offset)->limit($limit)
            ->get()->toArray();

        // 圖表：同條件下用戶數最多的前 10 張卡（不受分頁影響）
        $chartRows = $build()
            ->orderByRaw('user_count DESC, draw_count DESC')
            ->limit(10)->get()->toArray();

        // 卡片基礎信息（列表 + 圖表一次查齊）
        $cardIds = [];
        foreach (array_merge($rows, $chartRows) as $r) {
            $cardIds[] = (int)$r->card_id;
        }
        $cardIds = array_values(array_unique(array_filter($cardIds)));
        $cards = [];
        $sortMap = [];
        if (!empty($cardIds)) {
            $cardList = DB::table('card')->whereIn('card_id', $cardIds)
                ->select('card_id', 'card_name', 'en_card_name', 'card_img', 'card_stock', 'card_sort_id')
                ->get();
            foreach ($cardList as $c) {
                $cards[(int)$c->card_id] = $c;
            }
            $sortIds = [];
            foreach ($cardList as $c) {
                if (!empty($c->card_sort_id)) {
                    $sortIds[] = (int)$c->card_sort_id;
                }
            }
            $sortIds = array_values(array_unique($sortIds));
            if (!empty($sortIds)) {
                $sortRows = DB::table('card_sort')->whereIn('card_sort_id', $sortIds)
                    ->select('card_sort_id', 'card_sort_name', 'en_card_sort_name')->get();
                foreach ($sortRows as $s) {
                    $sortMap[(int)$s->card_sort_id] = $s;
                }
            }
        }

        // 列表行的抽取用戶明細（每張卡按用戶聚合）
        $userMap = [];
        $listCardIds = [];
        foreach ($rows as $r) {
            $listCardIds[] = (int)$r->card_id;
        }
        $listCardIds = array_values(array_unique(array_filter($listCardIds)));
        if (!empty($listCardIds)) {
            $userRows = DB::table('order_details as od')
                ->join('order as o', 'od.order_id', '=', 'o.order_id')
                ->leftJoin('users as u', 'od.user_id', '=', 'u.user_id')
                ->whereIn('od.product_id', $listCardIds)
                ->where('o.indent_type', 2)
                ->where('o.order_status', '<>', -1)
                ->where('od.details_status', '<>', -1)
                ->groupBy('od.product_id', 'od.user_id', 'u.user_name', 'u.user_email')
                ->select(
                    'od.product_id',
                    'od.user_id',
                    'u.user_name',
                    'u.user_email',
                    DB::raw('COUNT(*) as draw_count'),
                    DB::raw('MIN(od.created_at) as first_draw_at'),
                    DB::raw('MAX(od.created_at) as last_draw_at')
                )
                ->orderByRaw('draw_count DESC')
                ->get();
            foreach ($userRows as $u) {
                $userMap[(int)$u->product_id][] = [
                    'user_id'       => (int)$u->user_id,
                    'user_name'     => $u->user_name,
                    'user_email'    => $u->user_email,
                    'draw_count'    => (int)$u->draw_count,
                    'first_draw_at' => $u->first_draw_at,
                    'last_draw_at'  => $u->last_draw_at,
                ];
            }
        }

        $datas['data'] = array_map(function ($row) use ($cards, $sortMap, $userMap, $language) {
            $cardId = (int)$row->card_id;
            $card = $cards[$cardId] ?? null;
            $sort = ($card && !empty($card->card_sort_id)) ? ($sortMap[(int)$card->card_sort_id] ?? null) : null;
            $users = $userMap[$cardId] ?? [];
            $maxUserDraw = 0;
            foreach ($users as $u) {
                $maxUserDraw = max($maxUserDraw, $u['draw_count']);
            }
            $stock = $card ? (int)$card->card_stock : null;
            $fallbackName = $language === 'en_' ? ($row->en_product_name ?: $row->product_name) : $row->product_name;
            return [
                'card_id'        => $cardId,
                'card_name'      => $card ? ($language === 'en_' ? ($card->en_card_name ?: $card->card_name) : $card->card_name) : $fallbackName,
                'card_img'       => $card->card_img ?? '',
                'card_stock'     => $stock,
                'card_sort_id'   => $card ? (int)$card->card_sort_id : 0,
                'card_sort_name' => $sort ? ($language === 'en_' ? ($sort->en_card_sort_name ?: $sort->card_sort_name) : $sort->card_sort_name) : '',
                'draw_count'     => (int)$row->draw_count,
                'user_count'     => (int)$row->user_count,
                'max_user_draw'  => $maxUserDraw,
                'first_draw_at'  => $row->first_draw_at,
                'last_draw_at'   => $row->last_draw_at,
                'alert_level'    => ($stock !== null && $stock < 0) ? 2 : 1,
                'users'          => $users,
            ];
        }, $rows);
        $datas['data'] = array_values($datas['data']);

        // 圖表數據（卡牌名 + 用戶數 + 抽取次數）
        $chart = ['data' => [], 'aData' => [], 'fData' => []];
        foreach ($chartRows as $r) {
            $cardId = (int)$r->card_id;
            $card = $cards[$cardId] ?? null;
            $name = $card ? ($language === 'en_' ? ($card->en_card_name ?: $card->card_name) : $card->card_name) : ($language === 'en_' ? ($r->en_product_name ?: $r->product_name) : $r->product_name);
            $chart['data'][] = '#' . $cardId . ' ' . $name;
            $chart['aData'][] = (int)$r->user_count;
            $chart['fData'][] = (int)$r->draw_count;
        }
        $datas['chart'] = $chart;
        return $datas;
    }

    /**
     * 後台-某張卡牌的完整抽取鏈路（逐筆抽取記錄，時間精確到秒）
     * @param array $data ['card_id' => int, 'user_id' => int, 'user_name' => string, 'time' => [start, end]]
     * @param string $language
     * @return array|null
     */
    public function getMultiDrawCheckDetailWeb($data, $language = ''){
        $cardId = (int)($data['card_id'] ?? 0);
        if ($cardId <= 0) {
            return null;
        }

        $query = DB::table('order_details as od')
            ->join('order as o', 'od.order_id', '=', 'o.order_id')
            ->leftJoin('users as u', 'od.user_id', '=', 'u.user_id')
            ->leftJoin('card_draw_tasks as t', 'o.draw_task_id', '=', 't.task_id')
            ->where('od.product_id', $cardId)
            ->where('o.indent_type', 2)
            ->where('o.order_status', '<>', -1)
            ->where('od.details_status', '<>', -1)
            // 屏蔽 2026-07-17 深夜脚本批量抽卡测试数据
            ->whereNotBetween('od.created_at', ['2026-07-17 23:00:00', '2026-07-18 01:00:00']);

        if (!empty($data['user_id'])) {
            $query->where('od.user_id', (int)$data['user_id']);
        }
        if (!empty($data['user_name'])) {
            $query->where('u.user_name', 'like', '%' . $data['user_name'] . '%');
        }
        if (!empty($data['time']) && !empty($data['time'][0])) {
            $query->whereBetween('od.created_at', [$data['time'][0], $data['time'][1]]);
        }

        $rows = $query->select(
                'od.details_id',
                'od.order_id',
                'od.user_id',
                'od.product_id',
                'od.product_name',
                'od.en_product_name',
                'od.product_img',
                'od.product_price',
                'od.product_money',
                'od.exchange_status',
                'od.created_at',
                'o.order_no',
                'o.draw_task_id',
                'o.payment_at',
                'u.user_name',
                'u.user_email',
                'u.user_phone',
                't.status as task_status',
                't.total_cost as task_total_cost',
                't.created_at as task_created_at'
            )
            ->orderBy('od.created_at', 'asc')
            ->orderBy('od.details_id', 'asc')
            ->limit(max(1, min((int)($data['detail_limit'] ?? 2000), 10000)) + 1)
            ->get()->toArray();

        // 截断检测：多取 1 条判断是否被 limit 截断
        $detailLimit = max(1, min((int)($data['detail_limit'] ?? 2000), 10000));
        $isTruncated = count($rows) > $detailLimit;
        if ($isTruncated) {
            $rows = array_slice($rows, 0, $detailLimit);
        }

        $card = DB::table('card')->where('card_id', $cardId)
            ->select('card_id', 'card_name', 'en_card_name', 'card_img', 'card_stock', 'card_sort_id')->first();

        $cardSortName = '';
        $stock = null;
        if ($card) {
            $stock = (int)$card->card_stock;
            if (!empty($card->card_sort_id)) {
                $cardSortName = (string)DB::table('card_sort')
                    ->where('card_sort_id', (int)$card->card_sort_id)
                    ->value($language . 'card_sort_name');
            }
        }

        $userIds = [];
        foreach ($rows as $row) {
            if (!empty($row->user_id)) {
                $userIds[] = (int)$row->user_id;
            }
        }
        $userIds = array_values(array_unique($userIds));

        return [
            'card' => [
                'card_id'        => $cardId,
                'card_name'      => $card ? ($language === 'en_' ? ($card->en_card_name ?: $card->card_name) : $card->card_name) : '',
                'card_img'       => $card->card_img ?? '',
                'card_stock'     => $stock,
                'card_sort_id'   => $card ? (int)$card->card_sort_id : 0,
                'card_sort_name' => $cardSortName,
                'alert_level'    => ($stock !== null && $stock < 0) ? 2 : 1,
            ],
            'draw_count'  => count($rows),
            'user_count'  => count($userIds),
            'data'        => $rows,
            'is_truncated'=> $isTruncated ? 1 : 0,
            'detail_limit'=> $detailLimit,
        ];
    }

    /**
     * 寫入操作日誌（後台安全審計）
     * activity_type=13：卡牌多用戶抽取檢測
     * @param string $token 後台登錄 token
     * @param string $action 操作（查詢 / 查看詳情 / 導出）
     * @param string $detail 操作對象
     */
    public function writeMultiDrawAuditLog($token, $action, $detail = ''){
        try {
            $admin = Admins::where('token', $token)->select('admin_id', 'username')->first();
            if (empty($admin)) {
                return;
            }
            ActivityLog::insert([
                'admin_name'        => $admin['username'],
                'admin_id'          => $admin['admin_id'],
                'activity_type'     => 13,
                'activity_mode'     => 1,
                'activity_number'   => 0,
                'activity_value'    => '卡牌多用戶抽取檢測-' . $action . ($detail !== '' ? '：' . $detail : ''),
                'en_activity_value' => 'Multi-user draw check - ' . $action . ($detail !== '' ? ': ' . $detail : ''),
                'created_at'        => self::getDate(),
            ]);
        } catch (\Throwable $e) {
            // 日誌寫入失敗不影響主流程，但需告警以便運維發現
            Log::warning('卡牌多用戶抽取檢測-審計日誌寫入失敗', [
                'action'    => $action,
                'detail'    => $detail,
                'error'     => $e->getMessage(),
                'user'      => 'token=' . substr((string)$token, 0, 8) . '...',
            ]);
        }
    }

    /**
     * 處理卡包卡片（兌換或發貨）
     */
    public function handleCardPack($data){
        if (empty($data['type']) || !in_array((int)$data['type'], [1, 2])) {
            return $this->error(__('api.param_error'), 'type 僅支援 1=兌換鉆石 / 2=發貨');
        }

        // 兼容 APP 端可能傳的格式：數組 [1,2,3] / 逗號字符串 "1,2,3" / 單個 int
        $raw = $data['card_order_details_id'];
        if (is_array($raw)) {
            $details_ids = $raw;
        } elseif (is_string($raw) && strpos($raw, ',') !== false) {
            $details_ids = explode(',', $raw);
        } else {
            $details_ids = [$raw];
        }
        $details_ids = array_values(array_unique(array_filter(array_map('intval', $details_ids))));
        if (empty($details_ids)) {
            return $this->error(__('api.param_error'), '未選擇任何卡片');
        }

        $type = (int)$data['type'];
        $user_id = (int)$data['user_id'];

        DB::beginTransaction();
        try {
            // ========== 查詢匹配的 order_details（按 type 區分支持範圍） ==========
            // type=1 兌換：只查抽卡訂單（indent_type=2）
            // type=2 發貨：產品+抽卡都支持（indent_type IN 1,2）
            $indentTypes = $type === 1 ? [2] : [1, 2];

            $all = DB::table('order_details')
                ->join('order', 'order_details.order_id', '=', 'order.order_id')
                ->where('order_details.user_id', $user_id)
                ->whereIn('order.indent_type', $indentTypes)
                ->where('order_details.details_status', '<>', -1)
                ->where(function ($q) use ($details_ids) {
                    // 只精確匹配 details_id 或 order_id，不匹配 product_id（會跨訂單匹配同款產品）
                    $q->whereIn('order_details.details_id', $details_ids)
                      ->orWhereIn('order_details.order_id', $details_ids);
                })
                ->lockForUpdate()
                ->select('order_details.*', 'order.indent_type AS _indent_type')
                ->get();

            \Log::info('handleCardPack QUERY RESULT', [
                'sent_ids'       => $details_ids,
                'matched_ids'    => $all->pluck('details_id')->values()->toArray(),
                'matched_count'  => $all->count(),
                'all_rows_debug' => $all->map(function($r) {
                    return [
                        'details_id'      => $r->details_id,
                        'order_id'        => $r->order_id,
                        'product_id'      => $r->product_id,
                        'indent_type'     => $r->_indent_type ?? null,
                        'exchange_status' => $r->exchange_status ?? null,
                        'logistics_status'=> $r->logistics_status ?? null,
                        'user_id'         => $r->user_id,
                    ];
                })->values()->toArray(),
            ]);

            if ($all->isEmpty()) {
                DB::rollBack();
                return $this->error(__('api.param_error'), '部分卡片/產品不存在或非本用戶');
            }

            // ========== 狀態過濾：抽卡用 exchange_status，產品用 logistics_status ==========
            // 未處理的定義：
            //   - 抽卡訂單（indent_type=2）：exchange_status=0 且 logistics_status=0
            //   - 產品訂單（indent_type=1）：logistics_status=0
            $details = collect();
            $alreadyProcessed = collect();
            foreach ($all as $row) {
                $indent = (int)($row->_indent_type ?? 0);
                $isCard = $indent === 2;
                // 未處理 = (抽卡 & exchange_status=0) OR (產品 & logistics_status=0)
                $unprocessed = $isCard
                    ? (int)($row->exchange_status ?? 0) === 0 && (int)($row->logistics_status ?? 0) === 0
                    : (int)($row->logistics_status ?? 0) === 0;
                if ($unprocessed) {
                    $details->push($row);
                } else {
                    $alreadyProcessed->push($row);
                }
            }

            // 全部都已處理過 → 直接返回成功（冪等）
            if ($details->isEmpty()) {
                DB::commit();
                return $this->success(
                    sprintf('這 %d 項均已處理過，無需重複操作', count($all))
                );
            }

            // 部分已處理 → 只處理剩下的，已處理的跳過不報錯
            if (!$alreadyProcessed->isEmpty()) {
                \Log::info('handleCardPack: 跳過已處理的（冪等）', [
                    'user_id'              => $user_id,
                    'skipped_ids'          => $alreadyProcessed->pluck('details_id')->values()->toArray(),
                    'will_process_ids'     => $details->pluck('details_id')->values()->toArray(),
                ]);
            }

            $found_ids = $details->pluck('details_id')->values()->toArray();

            $systemInfo = new SystemInfo();
            $totalMoney = 0;
            $product_names = [];
            $en_product_names = [];

            if ($type == 1) {
                // 兌換為鉆石：用 Card 表的 card_price（不是 product_money）
                foreach ($details as $detail) {
                    $product = Card::where('card_id', $detail->product_id)->lockForUpdate()->first();
                    if (empty($product)) {
                        DB::rollBack();
                        return $this->error(__('api.param_error'), '卡片 #' . $detail->product_id . ' 已不存在');
                    }
                    $price = (float)($product->card_price ?? 0);
                    $totalMoney += $price;
                    $product_names[] = $detail->product_name;
                    $en_product_names[] = $detail->en_product_name ?? $detail->product_name;
                }

                if ($totalMoney <= 0) {
                    DB::rollBack();
                    return $this->error(__('api.param_error'), '卡片回收價為 0，無法兌換');
                }

                // 加鉆石（原子操作）
                $affected = User::where('user_id', $user_id)
                    ->where('user_coin', '>=', 0) // 確保用戶存在
                    ->increment('user_coin', $totalMoney);

                // 錢包流水（payment_mode=6：鑽石兌換專用，與 convertDiamondToCoin 一致）
                UserWalletRecord::insert([
                    'user_id' => $user_id,
                    'wallet_type' => 4,           // 鑽石/點數變動
                    'payment_mode' => 6,           // 鑽石兌換
                    'wallet_explain' => '批量卡片兌換点数：' . implode('、', $product_names),
                    'en_wallet_explain' => 'カードをコインに交換: ' . implode(', ', $en_product_names),
                    'wallet_money' => $totalMoney,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

                // 更新兌換狀態為已兌換，並記錄每張卡的實際兌換金額（供後台恢復時按原額扣點）
                foreach ($details as $detail) {
                    $exchangePrice = (float)(Card::where('card_id', $detail->product_id)->value('card_price') ?? 0);
                    DB::table('order_details')
                        ->where('details_id', $detail->details_id)
                        ->where('user_id', $user_id)
                        ->where('exchange_status', 0)
                        ->update([
                            'exchange_status' => 1,
                            'exchange_amount' => $exchangePrice,
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                }

                $content = "您的 " . count($details) . " 張卡片已成功兌換為 " . $totalMoney . " 点数。";
                $en_content = count($details) . "枚のカードが正常に" . $totalMoney . "コインに交換されました。";
                $systemInfo->addSystemInfo($user_id, 3, $content, $en_content);

            } else if ($type == 2) {
                // ========== 發貨（合併模式） ==========
                // 設計原則：
                //   1. 搬 order_details.order_id 到新建的合併訂單 → 後台發貨列表一條搞定
                //   2. 原訂單設 order_status=-1 隱藏（因為 details 已經被搬走了）
                //   3. 新合併訂單 indent_type=0（發貨合併特殊類型）
                //   4. logistics_status=0 等後台填物流單號，exchange_status=2 已申請發貨
                //   5. API 端 getOrderListApi 過濾掉 indent_type=0（不讓用戶看到內部合併單）
                $deliveryData = [];
                $deliveryData['delivery_name']     = $data['delivery_name'] ?? '';
                $deliveryData['delivery_phone']    = $data['delivery_phone'] ?? '';
                $deliveryData['delivery_area']     = $data['delivery_area'] ?? '';
                $deliveryData['delivery_address']  = $data['delivery_address'] ?? '';
                $deliveryData['delivery_mail']     = $data['delivery_mail'] ?? '';

                if (empty($deliveryData['delivery_name']) || empty($deliveryData['delivery_phone']) || empty($deliveryData['delivery_address'])) {
                    DB::rollBack();
                    return $this->error(__('api.param_error'), '請填寫完整的收貨信息');
                }

                // 計算總金額
                $totalMoney = 0;
                foreach ($details as $detail) {
                    $totalMoney += (float)($detail->product_money ?? 0);
                    $product_names[] = $detail->product_name;
                    $en_product_names[] = $detail->en_product_name ?? $detail->product_name;
                }

                // ========== 收集所有被搬空的原 order_id ==========
                // 注意：查詢結果的 order_id 就是原訂單的 order_details.order_id
                $originalOrderIds = $details->pluck('order_id')->unique()->values()->toArray();

                // ========== 創建合併發貨訂單 ==========
                // 注意：exchange_status / logistics_status 在 order_details 表，不在 order 表！
                $orderNo = 'SH' . date('YmdHis') . rand(1000, 9999);
                $newOrderId = DB::table('order')->insertGetId([
                    'order_no'          => $orderNo,
                    'user_id'           => $user_id,
                    'indent_type'       => 0,  // 0 = 發貨合併單
                    'order_money'       => $totalMoney,
                    'payment_mode'      => 6,  // 發貨專用
                    'payment_status'    => 3,  // 已支付
                    'order_status'      => 1,  // 正常
                    'order_remark'      => '發貨合併：' . implode(',', $originalOrderIds),
                    'delivery_name'     => $deliveryData['delivery_name'],
                    'delivery_phone'    => $deliveryData['delivery_phone'],
                    'delivery_area'     => $deliveryData['delivery_area'],
                    'delivery_address'  => $deliveryData['delivery_address'],
                    'delivery_mail'     => $deliveryData['delivery_mail'],
                    'created_at'        => date('Y-m-d H:i:s'),
                    'updated_at'        => date('Y-m-d H:i:s'),
                ]);

                if (!$newOrderId) {
                    DB::rollBack();
                    return $this->error('創建發貨訂單失敗');
                }

                // ========== 搬 order_details 到新合併訂單 ==========
                // 干净表已確認 order_details 有 exchange_status 字段
                // logistics_status=0 等後台填物流單號，exchange_status=2 表示已申請發貨
                // 注意：產品訂單（indent_type=1）的 detail 也必須設 exchange_status=2，
                // 否則合併單中含產品 detail 時，後台發貨詳情會誤判為「未兌換」
                $allDetailsIds = $details->pluck('details_id')->values()->toArray();

                DB::table('order_details')
                    ->whereIn('details_id', $allDetailsIds)
                    ->where('user_id', $user_id)
                    ->update([
                        'order_id'         => $newOrderId,
                        'logistics_status' => 0,
                        'exchange_status'  => 2,
                    ]);

                // ========== 同時搬 card_order_details 到合併單（兼容舊表，若不存在則跳過） ==========
                // 干净表中不存在此表，用 Schema::hasTable 防御，避免抛異常導致事務回滾
                try {
                    if (self::hasTableCached('card_order_details')) {
                        $cardOrderIds = collect($originalOrderIds)->filter(function($oid) {
                            $ind = DB::table('order')->where('order_id', $oid)->value('indent_type');
                            return (int)$ind === 2;
                        })->values()->toArray();
                        if (!empty($cardOrderIds)) {
                            // 字段名以 migration 定義為準：card_order_id（不是 order_id）
                            DB::table('card_order_details')
                                ->whereIn('card_order_id', $cardOrderIds)
                                ->update([
                                    'card_order_id'   => $newOrderId,
                                    'exchange_status' => 2,  // 2=已申請發貨
                                ]);
                        }
                    }
                } catch (\Throwable $e) {
                    \Log::warning('card_order_details 搬運失敗（不影響發貨）: ' . $e->getMessage());
                }

                // ========== 原訂單設為隱藏（order_status=-1） ==========
                // 但要先檢查原訂單是否還有其他 details（部分搬運的情況）
                foreach ($originalOrderIds as $oid) {
                    $remaining = DB::table('order_details')
                        ->where('order_id', $oid)
                        ->where('details_status', '<>', -1)
                        ->count();
                    if ($remaining === 0) {
                        // 原訂單已被搬空，隱藏它
                        DB::table('order')->where('order_id', $oid)->update(['order_status' => -1]);
                    }
                    // 如果原訂單還有其他 details，保持 order_status 不變
                }

                \Log::info('handleCardPack SHIPPING (MERGE MODE)', [
                    'new_order_id' => $newOrderId,
                    'moved_details' => count($allDetailsIds),
                    'original_orders_hidden' => $originalOrderIds,
                ]);

                // ========== 物流記錄寫入 card_order_logistics ==========
                try {
                    if (self::hasTableCached('card_order_logistics')) {
                        DB::table('card_order_logistics')->insert([
                            'card_order_details_id' => 0,  // 合併單，沒有單個 detail
                            'order_id'              => $newOrderId,
                            'user_id'               => $user_id,
                            'card_order_logistics_number' => '',
                            'logistics_status'      => 0,
                            'created_at'           => date('Y-m-d H:i:s'),
                            'updated_at'           => date('Y-m-d H:i:s'),
                        ]);
                    }
                } catch (\Throwable $e) {
                    \Log::warning('物流記錄寫入失敗（不影響發貨）: ' . $e->getMessage());
                }

                \Log::info('handleCardPack SUCCESS', [
                    'user_id' => $user_id,
                    'moved_details' => count($allDetailsIds),
                    'new_order_id' => $newOrderId,
                ]);

                // 寫入系統通知
                $cardCount = $details->filter(fn($r) => (int)($r->_indent_type ?? 0) === 2)->count();
                $productCount = $details->filter(fn($r) => (int)($r->_indent_type ?? 0) === 1)->count();
                $detailParts = [];
                if ($cardCount > 0)    $detailParts[] = "{$cardCount} 張卡牌";
                if ($productCount > 0) $detailParts[] = "{$productCount} 項產品";
                $content = "您的 " . implode('、', $detailParts) . " 已申請發貨，請耐心等待。";
                $systemInfo->addSystemInfo($user_id, 3, $content, $content);
            } else {
                DB::rollBack();
                return $this->error(__('api.param_error'));
            }

            DB::commit();

            // 統計卡牌 / 產品數量（從查詢結果的 _indent_type 計算）
            $cardCount = $details->filter(function($r) { return (int)($r->_indent_type ?? 0) === 2; })->count();
            $productCount = $details->filter(function($r) { return (int)($r->_indent_type ?? 0) === 1; })->count();

            $successMsg = $type == 1
                ? sprintf('成功兌換 %d 張卡片，獲得 %s 點', count($details), rtrim(rtrim(sprintf('%.2f', $totalMoney), '0'), '.'))
                : sprintf('成功發貨 %d 項（%d 張卡牌 + %d 項產品）', count($details), $cardCount, $productCount);
            return $this->success($successMsg);

        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('handleCardPack exception', [
                'user_id'  => $user_id,
                'type'     => $type,
                'details'  => $details_ids,
                'error'    => $e->getMessage(),
                'class'    => get_class($e),
                'trace'    => $e->getTraceAsString(),
            ]);
            return $this->error(__('api.operation_fail'), $e->getMessage());
        }
    }

/*********************************************************admin**********************************************************************/

    /**
     * 後台：已兌換卡片列表（篩選：用戶/時間/價格區間/卡牌名）
     * @param $data
     * @return array
     */
    public function getExchangedCardsWeb($data){
        $page  = max(1, (int)($data['page'] ?? 1));
        $limit = max(1, (int)($data['limit'] ?? 20));

        $query = DB::table('order_details as d')
            ->join('users as u', 'u.user_id', '=', 'd.user_id')
            ->leftJoin('card as c', 'c.card_id', '=', 'd.product_id')
            ->leftJoin('order as o', 'o.order_id', '=', 'd.order_id')
            ->where('d.exchange_status', 1)
            ->where('d.details_status', '<>', -1)
            ->select(
                'd.details_id', 'd.user_id', 'd.order_id', 'd.product_id',
                'd.product_name', 'd.en_product_name', 'd.product_img', 'd.product_money',
                'd.exchange_amount', 'd.updated_at as exchanged_at',
                'u.user_name', 'u.user_email', 'u.user_coin',
                'c.card_price', 'o.order_no'
            );

        // 用戶名 / 郵箱模糊
        if (!empty($data['keyword'])) {
            $kw = trim($data['keyword']);
            $query->where(function ($q) use ($kw) {
                $q->where('u.user_name', 'like', "%{$kw}%")
                  ->orWhere('u.user_email', 'like', "%{$kw}%");
            });
        }
        // 卡牌名模糊
        if (!empty($data['card_name'])) {
            $cn = trim($data['card_name']);
            $query->where(function ($q) use ($cn) {
                $q->where('d.product_name', 'like', "%{$cn}%")
                  ->orWhere('d.en_product_name', 'like', "%{$cn}%");
            });
        }
        // 價格區間（按實際兌換金額，老數據回退 card_price）
        $priceField = DB::raw('COALESCE(d.exchange_amount, c.card_price)');
        if (isset($data['price_min']) && $data['price_min'] !== '' && $data['price_min'] !== null) {
            $query->where($priceField, '>=', (float)$data['price_min']);
        }
        if (isset($data['price_max']) && $data['price_max'] !== '' && $data['price_max'] !== null) {
            $query->where($priceField, '<=', (float)$data['price_max']);
        }
        // 兌換時間範圍（updated_at 在兌換時寫入）
        if (!empty($data['start_time'])) {
            $query->where('d.updated_at', '>=', $data['start_time'] . ' 00:00:00');
        }
        if (!empty($data['end_time'])) {
            $query->where('d.updated_at', '<=', $data['end_time'] . ' 23:59:59');
        }

        $total = (clone $query)->count();
        $list = $query->orderBy('d.updated_at', 'desc')
            ->orderBy('d.details_id', 'desc')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get();

        foreach ($list as $row) {
            // 實際兌換金額（恢復時按此扣點）
            $row->restore_amount = $row->exchange_amount !== null
                ? (float)$row->exchange_amount
                : (float)($row->card_price ?? 0);
            $row->user_coin = (float)$row->user_coin;
        }

        return $this->success(__('web.success'), [
            'data'  => $list,
            'count' => $total,
        ]);
    }

    /**
     * 後台：恢復已兌換卡片（扣除用戶點數，卡片回到背包未處理狀態）
     * 前提：用戶賬號點數充足，否則拒絕
     * @param $data details_ids 數組
     * @param $token 管理員token（寫操作日誌）
     * @return array
     */
    public function restoreExchangedCardsWeb($data, $token){
        $detailsIds = $data['details_ids'] ?? [];
        if (!is_array($detailsIds) || empty($detailsIds)) {
            return $this->error(__('web.param_error'));
        }

        $admin = \App\Models\Admins::where('token', $token)->select('admin_id', 'username')->first();
        if (empty($admin)) {
            return $this->error(__('web.param_error'), '管理員登錄已失效');
        }

        DB::beginTransaction();
        try {
            $details = DB::table('order_details')
                ->whereIn('details_id', $detailsIds)
                ->where('exchange_status', 1)
                ->where('details_status', '<>', -1)
                ->lockForUpdate()
                ->get();

            if ($details->isEmpty()) {
                DB::rollBack();
                return $this->error(__('web.param_error'), '所選記錄不存在或已被恢復');
            }

            $systemInfo = new SystemInfo();
            $restoredCount = 0;

            // 按用戶分組：逐用戶校驗點數並扣款
            $byUser = $details->groupBy('user_id');
            foreach ($byUser as $userId => $rows) {
                $totalDeduct = 0;
                $cardNames = [];
                foreach ($rows as $row) {
                    $amount = $row->exchange_amount !== null
                        ? (float)$row->exchange_amount
                        : (float)(Card::where('card_id', $row->product_id)->value('card_price') ?? 0);
                    $totalDeduct += $amount;
                    $cardNames[] = $row->product_name;
                }

                // 前提：賬號有那麼多點數（原子扣款，不足則 affected=0）
                $affected = User::where('user_id', $userId)
                    ->where('user_coin', '>=', $totalDeduct)
                    ->decrement('user_coin', $totalDeduct);
                if (!$affected) {
                    DB::rollBack();
                    $u = User::where('user_id', $userId)->select('user_name', 'user_coin')->first();
                    return $this->error(
                        __('web.operation_fail'),
                        "用戶 {$u->user_name} 點數不足：需扣除 {$totalDeduct}，當前僅 " . (float)$u->user_coin
                    );
                }

                // 卡片回到未處理狀態
                DB::table('order_details')
                    ->whereIn('details_id', $rows->pluck('details_id')->all())
                    ->where('exchange_status', 1)
                    ->update([
                        'exchange_status' => 0,
                        'exchange_amount' => null,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);

                // 錢包負數流水
                UserWalletRecord::insert([
                    'user_id' => $userId,
                    'wallet_type' => 4,
                    'payment_mode' => 6,
                    'wallet_explain' => '後台恢復已兌換卡片，扣除点数：' . implode('、', $cardNames),
                    'en_wallet_explain' => 'Restore exchanged cards, deduct coins: ' . implode(', ', $cardNames),
                    'wallet_money' => -$totalDeduct,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                // 用戶系統通知
                $content = "管理員已恢復您的 " . count($rows) . " 張卡片到背包，扣除 " . $totalDeduct . " 点数。";
                $systemInfo->addSystemInfo($userId, 3, $content, $content);

                $restoredCount += count($rows);
            }

            // 管理員操作日誌（activity_log：哪個管理員、什麼時間、恢復了什麼）
            \App\Models\ActivityLog::insert([
                'admin_id' => $admin->admin_id,
                'admin_name' => $admin->username,
                'order_id' => $details->first()->order_id,
                'activity_type' => 7, // 7：恢復已兌換卡片
                'activity_mode' => 2, // 2：減少（扣點）
                'activity_number' => $restoredCount,
                'activity_value' => '恢復已兌換卡片 ' . $restoredCount . ' 張：' . implode('、', $details->pluck('product_name')->take(5)->all()) . (count($details) > 5 ? ' 等' : ''),
                'en_activity_value' => 'Restore exchanged cards x' . $restoredCount,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            DB::commit();
            return $this->success(sprintf('成功恢復 %d 張卡片', $restoredCount));
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('restoreExchangedCardsWeb exception', [
                'admin_id' => $admin->admin_id ?? 0,
                'details_ids' => $detailsIds,
                'error' => $e->getMessage(),
            ]);
            return $this->error(__('web.operation_fail'), $e->getMessage());
        }
    }
}
