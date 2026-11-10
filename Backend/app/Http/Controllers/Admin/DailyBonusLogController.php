<?php
/*
 * 每日登錄獎勵（金幣/鑽石）後台日誌
 */

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DailyBonusLogController extends \App\Http\Controllers\Controller
{
    /**
     * 獲取每日登錄獎勵列表（僅查 每日登錄送金幣/鑽石）
     */
    public function getDailyBonusLogListWeb(Request $request)
    {
        $data = $request->post();
        $limit  = (int)($data['limit'] ?? 10);
        $page   = max(1, (int)($data['page'] ?? 1));
        $offset = ($page - 1) * $limit;

        $query = DB::table('user_wallet_record as w')
            ->leftJoin('users', 'users.user_id', '=', 'w.user_id')
            ->select(
                'w.wallet_id',
                'w.user_id',
                'w.wallet_type',
                'w.payment_mode',
                'w.wallet_explain',
                'w.wallet_money',
                'w.created_at',
                'users.user_name',
                'users.user_email',
                'users.user_phone',
                'users.user_status'
            )
            ->whereIn('w.wallet_explain', ['每日登錄送金幣', '每日登錄送鑽石']);

        // 搜索：用戶ID / 用戶名 / 郵箱
        if (!empty($data['multi'])) {
            $kw = $data['multi'];
            $query->where(function ($q) use ($kw) {
                $q->where('w.user_id', '=', (int)$kw)
                  ->orWhere('users.user_name', 'like', "%{$kw}%")
                  ->orWhere('users.user_email', 'like', "%{$kw}%");
            });
        }

        // 類型篩選（金幣 or 鑽石）
        if (!empty($data['wallet_explain'])) {
            $query->where('w.wallet_explain', $data['wallet_explain']);
        }

        // 日期範圍篩選
        if (!empty($data['date_from'])) {
            $query->where('w.created_at', '>=', $data['date_from'] . ' 00:00:00');
        }
        if (!empty($data['date_to'])) {
            $query->where('w.created_at', '<=', $data['date_to'] . ' 23:59:59');
        }

        $total = $query->count();

        // 排序：支持 wallet_id / wallet_money / created_at
        $sort  = isset($data['sort']) && in_array($data['sort'], ['wallet_id', 'wallet_money', 'created_at'])
            ? $data['sort'] : 'wallet_id';
        $order = isset($data['order']) && in_array(strtolower($data['order']), ['asc', 'desc'])
            ? $data['order'] : 'desc';

        $list = $query->orderBy("w.{$sort}", $order)
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->toArray();

        // 今日領取人數（用於頁面頂部統計）
        $todayCount = DB::table('user_wallet_record')
            ->whereIn('wallet_explain', ['每日登錄送金幣', '每日登錄送鑽石'])
            ->whereBetween('created_at', [date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59')])
            ->distinct('user_id')
            ->count('user_id');

        // 今日發放總額
        $todayTotal = DB::table('user_wallet_record')
            ->whereIn('wallet_explain', ['每日登錄送金幣', '每日登錄送鑽石'])
            ->whereBetween('created_at', [date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59')])
            ->sum('wallet_money');

        return $this->success('ok', [
            'data'        => $list,
            'count'       => $total,
            'today_count' => $todayCount,
            'today_total' => (int)$todayTotal,
        ]);
    }
}
