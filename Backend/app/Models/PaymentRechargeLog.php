<?php

namespace App\Models;

use Illuminate\Support\Facades\Schema;

/**
 * 用戶充值記錄（payment_recharge_log）
 * 每筆成功入帳寫一條，trade_no 為支付網關流水號（唯一鍵，幂等）。
 */
class PaymentRechargeLog extends Base
{
    protected $table = 'payment_recharge_log';
    protected $primaryKey = 'id';
    public $timestamps = true;

    /**
     * 後台-用戶充值記錄列表
     * @param $data
     * @return array
     */
    public function getRechargeLogListWeb($data)
    {
        $limit = empty($data['limit']) ? 10 : $data['limit'];
        $page = ($data['page'] - 1) * $limit;
        $time = [];

        $where = [];
        if (!empty($data['user_id'])) {
            $where[] = array('payment_recharge_log.user_id', '=', (int)$data['user_id']);
        }
        if (!empty($data['user_name'])) {
            $where[] = array('users.user_name', 'like', '%' . $data['user_name'] . '%');
        }
        if (!empty($data['user_email'])) {
            $where[] = array('users.user_email', 'like', '%' . $data['user_email'] . '%');
        }
        if (!empty($data['trade_no'])) {
            $where[] = array('payment_recharge_log.trade_no', 'like', '%' . $data['trade_no'] . '%');
        }
        if (isset($data['payment_mode']) && $data['payment_mode'] !== '') {
            $where[] = array('payment_recharge_log.payment_mode', '=', (int)$data['payment_mode']);
        }
        if (!empty($data['time'])) {
            $time['start_time'] = $data['time'][0];
            $time['end_time'] = $data['time'][1];
        }

        // 每次調用獨立構造查詢，避免 data / count / sum 共用同一 builder 互相污染
        $build = function () use ($where, $time) {
            return $this->join('users', 'users.user_id', '=', 'payment_recharge_log.user_id')
                ->where($where)
                ->where(function ($query) use ($time) {
                    if (!empty($time) && $time['start_time'] != '') {
                        return $query->whereBetween('payment_recharge_log.created_at', $time);
                    }
                });
        };

        $columns = [
            'payment_recharge_log.id',
            'payment_recharge_log.user_id',
            'payment_recharge_log.trade_no',
            'payment_recharge_log.recharge_money',
            'payment_recharge_log.payment_mode',
            'payment_recharge_log.status',
            'payment_recharge_log.created_at',
            'users.user_name',
            'users.user_email',
            'users.user_phone',
            'users.user_balance'
        ];
        // 未跑遷移前不查該欄位，避免整個充值記錄列表報錯
        $hasWonderOrderNo = self::hasColumnCached('payment_recharge_log', 'wonder_order_no');
        if ($hasWonderOrderNo) {
            $columns[] = 'payment_recharge_log.wonder_order_no';
        }
        // 實際支付金額 + 贈送金幣（遷移後才有）
        $hasPaidAmount = self::hasColumnCached('payment_recharge_log', 'paid_amount');
        if ($hasPaidAmount) {
            $columns[] = 'payment_recharge_log.paid_amount';
        }
        $hasBonusCoin = self::hasColumnCached('payment_recharge_log', 'bonus_coin');
        if ($hasBonusCoin) {
            $columns[] = 'payment_recharge_log.bonus_coin';
        }

        $rows = $build()
            ->select($columns)
            ->orderBy('payment_recharge_log.id', 'desc')
            ->offset($page)->limit($limit)
            ->get()->toArray();

        // 到賬校驗：本表的入帳標記與 users.user_balance 的加錢在同一事務寫入，status=1 即已入帳
        foreach ($rows as &$row) {
            $row['audit_status'] = (int)$row['status'] === 1 ? 1 : 2;
            $row['audit_msg'] = (int)$row['status'] === 1 ? '' : '充值未成功入帳';
            if (!$hasWonderOrderNo) {
                $row['wonder_order_no'] = '';
            }
        }
        unset($row);

        $datas['data'] = $rows;
        $datas['count'] = $build()->count();
        $datas['total_money'] = number_format((float)$build()->sum('payment_recharge_log.recharge_money'), 2, '.', '');
        return $datas;
    }
}
