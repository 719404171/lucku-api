<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 抽卡批次記錄（card_draw_tasks）
 * 一次抽卡寫一條：階段1扣款/扣庫存後落任務，階段2建單成功置 status=1。
 * 逐張卡的明細見 CardOrderDetails::getDrawDetailListWeb()。
 */
class CardDrawTask extends Base
{
    protected $table = 'card_draw_tasks';
    protected $primaryKey = 'task_id';
    public $timestamps = true;

    /**
     * 後台-抽卡批次列表
     * @param $data
     * @param $language
     * @return array
     */
    public function getDrawTaskListWeb($data, $language = '')
    {
        $limit = empty($data['limit']) ? 10 : $data['limit'];
        $page = ($data['page'] - 1) * $limit;
        $time = [];

        $where = [];
        if (!empty($data['task_id'])) {
            $where[] = array('card_draw_tasks.task_id', '=', (int)$data['task_id']);
        }
        if (!empty($data['user_id'])) {
            $where[] = array('card_draw_tasks.user_id', '=', (int)$data['user_id']);
        }
        if (!empty($data['user_name'])) {
            $where[] = array('users.user_name', 'like', '%' . $data['user_name'] . '%');
        }
        if (!empty($data['user_email'])) {
            $where[] = array('users.user_email', 'like', '%' . $data['user_email'] . '%');
        }
        if (!empty($data['card_sort_id'])) {
            $where[] = array('card_draw_tasks.card_sort_id', '=', (int)$data['card_sort_id']);
        }
        if (isset($data['status']) && $data['status'] !== '') {
            $where[] = array('card_draw_tasks.status', '=', (int)$data['status']);
        }
        if (!empty($data['time'])) {
            $time['start_time'] = $data['time'][0];
            $time['end_time'] = $data['time'][1];
        }

        // 每次調用獨立構造查詢，避免 data / count / sum 共用同一 builder 互相污染
        $build = function () use ($where, $time) {
            return $this->join('users', 'users.user_id', '=', 'card_draw_tasks.user_id')
                ->where($where)
                ->where(function ($query) use ($time) {
                    if (!empty($time) && $time['start_time'] != '') {
                        return $query->whereBetween('card_draw_tasks.created_at', $time);
                    }
                });
        };

        $datas['data'] = $build()
            ->leftJoin('card_sort', 'card_sort.card_sort_id', '=', 'card_draw_tasks.card_sort_id')
            ->select(
                'card_draw_tasks.task_id',
                'card_draw_tasks.user_id',
                'card_draw_tasks.card_sort_id',
                'card_draw_tasks.total_cost',
                'card_draw_tasks.status',
                'card_draw_tasks.retry_times',
                'card_draw_tasks.created_at',
                'users.user_name',
                'users.user_email',
                'users.user_phone',
                'card_sort.' . $language . 'card_sort_name as card_sort_name',
                // cards_json 只用于页面显示张数，交由 PHP 解析（线上 MySQL 不支持 JSON_VALID/JSON_LENGTH）
                'card_draw_tasks.cards_json'
            )
            ->orderBy('card_draw_tasks.task_id', 'desc')
            ->offset($page)->limit($limit)
            ->get()->toArray();

        foreach ($datas['data'] as &$row) {
            $cards = json_decode($row['cards_json'] ?? '', true);
            $row['card_count'] = is_array($cards) ? count($cards) : 0;
            unset($row['cards_json']);
        }
        unset($row);

        $datas['count'] = $build()->count();
        $datas['total_money'] = number_format((float)$build()->sum('card_draw_tasks.total_cost'), 2, '.', '');
        return $datas;
    }

    /**
     * 後台-抽卡對賬列表（每次抽卡一行）
     * 以扣款流水 user_wallet_record.draw_task_id 精確關聯，逐筆校驗：
     * 是否扣款成功、扣款金額是否一致、是否已建單成功。
     * @param $data
     * @param $language
     * @return array
     */
    public function getDrawAuditListWeb($data, $language = '')
    {
        $limit = empty($data['limit']) ? 10 : $data['limit'];
        $page = ($data['page'] - 1) * $limit;
        $time = [];

        $where = [];
        if (!empty($data['task_id'])) {
            $where[] = array('card_draw_tasks.task_id', '=', (int)$data['task_id']);
        }
        if (!empty($data['user_id'])) {
            $where[] = array('card_draw_tasks.user_id', '=', (int)$data['user_id']);
        }
        if (!empty($data['user_name'])) {
            $where[] = array('users.user_name', 'like', '%' . $data['user_name'] . '%');
        }
        if (!empty($data['user_email'])) {
            $where[] = array('users.user_email', 'like', '%' . $data['user_email'] . '%');
        }
        if (!empty($data['card_sort_id'])) {
            $where[] = array('card_draw_tasks.card_sort_id', '=', (int)$data['card_sort_id']);
        }
        if (isset($data['status']) && $data['status'] !== '') {
            $where[] = array('card_draw_tasks.status', '=', (int)$data['status']);
        }
        if (!empty($data['time'])) {
            $time['start_time'] = $data['time'][0];
            $time['end_time'] = $data['time'][1];
        }

        $onlyAbnormal = !empty($data['only_abnormal']);

        // 異常判定條件（與 PHP 端校驗保持同一口徑）
        $abnormalWhere = function ($query) {
            $query->whereNull('w.wallet_id')
                ->orWhereColumn('w.wallet_money', '<>', 'card_draw_tasks.total_cost')
                ->orWhere('card_draw_tasks.status', '<>', 1);
        };

        // 每次調用獨立構造查詢，避免 data / count 共用同一 builder 互相污染
        $build = function ($abnormalOnly = false) use ($where, $time, $abnormalWhere) {
            $query = $this->join('users', 'users.user_id', '=', 'card_draw_tasks.user_id')
                ->leftJoin('user_wallet_record as w', function ($join) {
                    $join->on('w.draw_task_id', '=', 'card_draw_tasks.task_id')
                        ->where('w.wallet_type', '=', 1);
                })
                ->where($where)
                ->where(function ($sub) use ($time) {
                    if (!empty($time) && $time['start_time'] != '') {
                        return $sub->whereBetween('card_draw_tasks.created_at', $time);
                    }
                });
            if ($abnormalOnly) {
                $query->where($abnormalWhere);
            }
            return $query;
        };

        $rows = $build($onlyAbnormal)
            ->leftJoin('card_sort', 'card_sort.card_sort_id', '=', 'card_draw_tasks.card_sort_id')
            ->select(
                'card_draw_tasks.task_id',
                'card_draw_tasks.user_id',
                'card_draw_tasks.card_sort_id',
                'card_draw_tasks.total_cost',
                'card_draw_tasks.status',
                'card_draw_tasks.retry_times',
                'card_draw_tasks.cards_json',
                'card_draw_tasks.created_at',
                'users.user_name',
                'users.user_email',
                'users.user_balance',
                'card_sort.' . $language . 'card_sort_name as card_sort_name',
                'w.wallet_id',
                'w.wallet_money'
            )
            ->orderBy('card_draw_tasks.task_id', 'desc')
            ->offset($page)->limit($limit)
            ->get()->toArray();

        foreach ($rows as &$row) {
            $cards = json_decode($row['cards_json'] ?? '', true);
            $cards = is_array($cards) ? $cards : [];
            $names = array_values(array_filter(array_column($cards, 'card_name')));
            $row['card_count'] = count($cards);
            $row['cards_text'] = count($names) > 5
                ? implode('、', array_slice($names, 0, 5)) . ' 等' . count($names) . '張'
                : implode('、', $names);
            unset($row['cards_json']);

            $cost = (float)$row['total_cost'];
            $paid = $row['wallet_id'] ? (float)$row['wallet_money'] : null;
            // 是否得到卡牌：建單成功(status=1)才算真正發卡到用戶
            $row['got_cards'] = (int)$row['status'] === 1 ? 1 : 0;
            if ($paid === null) {
                $row['pay_status'] = 0;
                $row['audit_status'] = 2;
                $row['audit_msg'] = '未找到扣款流水';
                $row['audit_advice'] = '先核對該筆錢包流水：若是上線前的舊數據（無法回填關聯）可忽略；若是新數據，先暫停該卡池抽卡開關並聯繫技術排查';
            } elseif (abs($paid - $cost) > 0.001) {
                $row['pay_status'] = 1;
                $row['audit_status'] = 2;
                $row['audit_msg'] = '扣款金額不符（應扣 ' . $cost . '，實際 ' . $paid . '）';
                $row['audit_advice'] = '核對卡池單價與該筆流水，差額需人工補扣或退還；並確認卡池價格在抽卡前後是否被修改';
            } elseif ((int)$row['status'] !== 1) {
                $row['pay_status'] = 1;
                $row['audit_status'] = 2;
                $row['audit_msg'] = (int)$row['status'] === 0 ? '已扣款但未建單（用戶未得到卡牌）' : '建單重試超限，待人工處理（用戶未得到卡牌）';
                $row['audit_advice'] = (int)$row['status'] === 0
                    ? '系統每 5 分鐘自動補建單（draw-order:retry）；若長時間仍為待建單，手動執行 php artisan draw-order:retry，或按卡片內容人工補發'
                    : '需人工處理：按卡片內容為用戶補發卡牌，或退還該筆金幣（' . $cost . '）後標記完成';
            } else {
                $row['pay_status'] = 1;
                $row['audit_status'] = 1;
                $row['audit_msg'] = '';
                $row['audit_advice'] = '';
            }
        }
        unset($row);

        $datas['data'] = $rows;
        $datas['count'] = $build($onlyAbnormal)->count();
        $datas['abnormal_count'] = $build(true)->count();
        $datas['total_money'] = number_format((float)$build($onlyAbnormal)->sum('card_draw_tasks.total_cost'), 2, '.', '');
        return $datas;
    }

    /**
     * 後台-單個抽卡批次對賬單（追溯該批次的付款、發卡與賬戶變化）
     * @param array $data ['task_id' => int]
     * @param string $language
     * @return array|null
     */
    public function getDrawBillWeb($data, $language = '')
    {
        $taskId = (int)($data['task_id'] ?? 0);
        if ($taskId <= 0) {
            return null;
        }

        $task = DB::table('card_draw_tasks')
            ->leftJoin('users', 'users.user_id', '=', 'card_draw_tasks.user_id')
            ->leftJoin('card_sort', 'card_sort.card_sort_id', '=', 'card_draw_tasks.card_sort_id')
            ->where('card_draw_tasks.task_id', $taskId)
            ->select(
                'card_draw_tasks.task_id',
                'card_draw_tasks.user_id',
                'card_draw_tasks.card_sort_id',
                'card_draw_tasks.total_cost',
                'card_draw_tasks.status',
                'card_draw_tasks.retry_times',
                'card_draw_tasks.cards_json',
                'card_draw_tasks.created_at',
                'card_draw_tasks.updated_at',
                'users.user_name',
                'users.user_email',
                'users.user_phone',
                'users.user_balance',
                'users.user_coin',
                'users.user_integral',
                'users.user_status',
                'users.ban_until',
                'users.ban_reason',
                'card_sort.' . $language . 'card_sort_name as card_sort_name'
            )
            ->first();
        if (empty($task)) {
            return null;
        }
        $task = (array)$task;

        $userId = (int)$task['user_id'];
        $cost = (float)$task['total_cost'];
        $status = (int)$task['status'];

        // 1) 扣款流水（draw_task_id 精確關聯，抽卡扣費 wallet_type=1）
        $wallet = DB::table('user_wallet_record')
            ->where('draw_task_id', $taskId)
            ->where('wallet_type', 1)
            ->orderBy('wallet_id')
            ->first();

        $payStatus = 0;
        $payMsg = '未找到扣款流水';
        $diff = null;
        if (!empty($wallet)) {
            $paid = (float)$wallet->wallet_money;
            $diff = round($cost - $paid, 2);
            if (abs($diff) > 0.001) {
                $payMsg = '扣款金額不符（應扣 ' . $cost . '，實際 ' . $paid . '）';
            } else {
                $payStatus = 1;
                $payMsg = '';
            }
        }

        // 2) 訂單與發卡（新數據用 order.draw_task_id 精確關聯，舊數據退化为「用戶+時間窗」近似）
        $issued = $this->getBillOrders($taskId, $userId, $task['created_at']);

        // 3) 逐張校驗卡片是否真的發到用戶手上（按 card_id 出現次數消耗）
        $cards = json_decode($task['cards_json'] ?? '', true);
        $cards = is_array($cards) ? $cards : [];
        unset($task['cards_json']);

        $pool = $issued['card_ids'];
        $issuedCount = 0;
        $cardList = [];
        foreach ($cards as $card) {
            $cardId = (int)($card['card_id'] ?? 0);
            $pos = $cardId > 0 ? array_search($cardId, $pool) : false;
            $ok = $pos !== false;
            if ($ok) {
                unset($pool[$pos]);
                $issuedCount++;
            }
            $cardList[] = [
                'card_id'    => $cardId,
                'card_name'  => $card['card_name'] ?? '',
                'card_img'   => $card['card_img'] ?? '',
                'card_price' => isset($card['card_price']) ? (float)$card['card_price'] : 0,
                'card_type'  => $card['card_type'] ?? '',
                'issued'     => $ok ? 1 : 0
            ];
        }

        // 4) 賬戶資金追溯：無餘額快照字段，抽卡前後餘額由「當前餘額 − 該批次之後的金幣收支淨額」反推
        $flow = $this->getCoinFlowAfter($userId, empty($wallet) ? 0 : (int)$wallet->wallet_id);
        $balanceNow = (float)$task['user_balance'];
        $balanceAfter = $flow['net'] === null ? null : round($balanceNow - $flow['net'], 2);
        $balanceBefore = $balanceAfter === null ? null : round($balanceAfter + $cost, 2);

        return [
            'task' => $task + [
                'got_cards'   => $status === 1 ? 1 : 0,
                'status_text' => ['0' => '待建單', '1' => '已建單', '2' => '重試超限待人工'][(string)$status] ?? ''
            ],
            'account' => [
                'user_balance'  => (float)$task['user_balance'],
                'user_coin'     => (float)$task['user_coin'],
                'user_integral' => (int)$task['user_integral'],
                'user_status'   => (int)$task['user_status'],
                'ban_until'     => $task['ban_until'],
                'ban_reason'    => $task['ban_reason'],
                'is_banned'     => ((int)$task['user_status'] !== 1 || !empty($task['ban_until'])) ? 1 : 0
            ],
            'payment' => [
                'wallet_id'      => empty($wallet) ? 0 : (int)$wallet->wallet_id,
                'wallet_money'   => empty($wallet) ? null : (float)$wallet->wallet_money,
                'wallet_explain' => empty($wallet) ? '' : $wallet->wallet_explain,
                'created_at'     => empty($wallet) ? '' : $wallet->created_at,
                'pay_status'     => $payStatus,
                'diff'           => $diff,
                'msg'            => $payMsg
            ],
            'balance' => [
                'current'       => $balanceNow,
                'after_draw'    => $balanceAfter,
                'before_draw'   => $balanceBefore,
                'net_after'     => $flow['net'],
                'income_after'  => $flow['income'],
                'expense_after' => $flow['expense'],
                'note'          => '無餘額快照字段，抽卡前後餘額由「當前餘額 − 該批次之後的金幣收支淨額」反推，僅供對賬參考'
            ],
            'cards'        => $cardList,
            'issued_count' => $issuedCount,
            'orders'       => $issued,
            'timeline'     => $flow['timeline'],
            'audit'        => [
                'got_cards'    => $status === 1 ? 1 : 0,
                'pay_status'   => $payStatus,
                'audit_status' => ($payStatus === 1 && $status === 1) ? 1 : 2,
                'msg'          => $payMsg !== '' ? $payMsg : ($status === 1 ? '' : '已扣款但未建單，用戶未得到卡牌')
            ]
        ];
    }

    /**
     * 金幣流水口徑（與 App 端「我的錢包」一致）：type in (1,2,3) 或 (type=6 且 payment_mode=3)，並排除鑽石文案
     * @param int $userId
     * @return \Illuminate\Database\Query\Builder
     */
    private function coinFlowQuery(int $userId)
    {
        return DB::table('user_wallet_record')
            ->where('user_id', $userId)
            ->where(function ($q) {
                $q->where('wallet_status', '<>', -1)->orWhereNull('wallet_status');
            })
            ->where(function ($q) {
                $q->whereIn('wallet_type', [1, 2, 3])
                    ->orWhere(function ($sub) {
                        $sub->where('wallet_type', 6)->where('payment_mode', 4);
                    });
            })
            // 邀請獎勵鑽石被錯記為 wallet_type=1，這裡按文案排除，避免污染金幣餘額反推
            ->where(function ($q) {
                $q->where('wallet_explain', 'not like', '%鑽石%')->orWhereNull('wallet_explain');
            })
            ->where(function ($q) {
                $q->where('en_wallet_explain', 'not like', '%diamond%')->orWhereNull('en_wallet_explain');
            });
    }

    /**
     * 以某筆流水為錨點，反推金幣餘額變化與該筆前後 20 筆的資金時間線
     * @param int $userId
     * @param int $walletId
     * @return array
     */
    private function getCoinFlowAfter(int $userId, int $walletId): array
    {
        if ($userId <= 0 || $walletId <= 0) {
            return ['net' => null, 'income' => null, 'expense' => null, 'timeline' => []];
        }

        // 該筆之後的金幣收支淨額（收入取 +abs，支出取 -abs），wallet_type=2 為入賬，其餘為出賬
        $netSql = 'COALESCE(SUM(CASE WHEN wallet_type = 2 THEN ABS(wallet_money) ELSE -ABS(wallet_money) END), 0) as net';
        $net = (float)$this->coinFlowQuery($userId)->where('wallet_id', '>', $walletId)->selectRaw($netSql)->value('net');

        $sumSql = 'COALESCE(SUM(ABS(wallet_money)), 0) as m';
        $income = (float)$this->coinFlowQuery($userId)->where('wallet_id', '>', $walletId)
            ->where('wallet_type', 2)->selectRaw($sumSql)->value('m');
        $expense = (float)$this->coinFlowQuery($userId)->where('wallet_id', '>', $walletId)
            ->whereIn('wallet_type', [1, 3])->selectRaw($sumSql)->value('m');
        $expense += (float)$this->coinFlowQuery($userId)->where('wallet_id', '>', $walletId)
            ->where('wallet_type', 6)->selectRaw($sumSql)->value('m');

        // 由最新一筆往回累加，得到每筆變動後的餘額（取最近 1000 筆，足夠覆蓋對賬窗口）
        $recent = $this->coinFlowQuery($userId)
            ->orderByDesc('wallet_id')->limit(1000)
            ->select('wallet_id', 'wallet_type', 'wallet_money')
            ->get();
        $running = (float)DB::table('users')->where('user_id', $userId)->value('user_balance');
        $balanceMap = [];
        foreach ($recent as $row) {
            $balanceMap[(int)$row->wallet_id] = round($running, 2);
            $running -= ((int)$row->wallet_type === 2 ? 1 : -1) * abs((float)$row->wallet_money);
        }

        $timeline = [];
        $rows = $this->coinFlowQuery($userId)
            ->whereBetween('wallet_id', [$walletId - 20, $walletId + 20])
            ->orderByDesc('wallet_id')
            ->select('wallet_id', 'wallet_type', 'wallet_money', 'wallet_explain', 'created_at')
            ->get();
        foreach ($rows as $row) {
            $wid = (int)$row->wallet_id;
            $timeline[] = [
                'wallet_id'      => $wid,
                'created_at'     => $row->created_at,
                'wallet_explain' => $row->wallet_explain,
                'direction'      => (int)$row->wallet_type === 2 ? 'in' : 'out',
                'money'          => abs((float)$row->wallet_money),
                'balance_after'  => $balanceMap[$wid] ?? null,
                'is_current'     => $wid === $walletId ? 1 : 0
            ];
        }

        return [
            'net'      => round($net, 2),
            'income'   => round($income, 2),
            'expense'  => round($expense, 2),
            'timeline' => $timeline
        ];
    }

    /**
     * 取該批次對應的訂單與明細（用於核對卡牌是否真的開單發到用戶手上）
     * @param int $taskId
     * @param int $userId
     * @param string $taskCreatedAt
     * @return array{mode: string, rows: array, card_ids: array}
     */
    private function getBillOrders(int $taskId, int $userId, $taskCreatedAt): array
    {
        $select = ['order_id', 'order_no', 'order_money', 'order_status', 'payment_at', 'created_at'];
        $mode = 'none';
        $orders = collect();

        if (self::hasColumnCached('order', 'draw_task_id')) {
            $orders = DB::table('order')
                ->where('draw_task_id', $taskId)
                ->where('indent_type', 2)
                ->where('order_status', '<>', -1)
                ->orderBy('order_id')
                ->select($select)
                ->get();
            if ($orders->isNotEmpty()) {
                $mode = 'task_id';
            }
        }

        if ($orders->isEmpty() && !empty($taskCreatedAt)) {
            // 歷史訂單沒有 draw_task_id：退化为「同一用戶 + 抽卡後 10 分鐘內」，僅作近似關聯
            $orders = DB::table('order')
                ->where('user_id', $userId)
                ->where('indent_type', 2)
                ->where('order_status', '<>', -1)
                ->whereBetween('created_at', [$taskCreatedAt, date('Y-m-d H:i:s', strtotime($taskCreatedAt) + 600)])
                ->orderBy('order_id')
                ->select($select)
                ->get();
            if ($orders->isNotEmpty()) {
                $mode = 'time_window';
            }
        }

        $orderIds = $orders->pluck('order_id')->all();
        $details = empty($orderIds)
            ? collect()
            : DB::table('order_details')
                ->whereIn('order_id', $orderIds)
                ->where('details_status', '<>', -1)
                ->select('details_id', 'order_id', 'product_id', 'product_name', 'product_price')
                ->orderBy('details_id')
                ->get();

        $rows = [];
        foreach ($orders as $order) {
            $order = (array)$order;
            $order['details'] = [];
            foreach ($details->where('order_id', $order['order_id']) as $detail) {
                $order['details'][] = [
                    'details_id'    => (int)$detail->details_id,
                    'product_id'    => (int)$detail->product_id,
                    'product_name'  => $detail->product_name,
                    'product_price' => (float)$detail->product_price
                ];
            }
            $rows[] = $order;
        }

        return [
            'mode'     => $mode,
            'rows'     => $rows,
            'card_ids' => $details->map(function ($detail) {
                return (int)$detail->product_id;
            })->all()
        ];
    }
}
