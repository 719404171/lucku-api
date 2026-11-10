<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * 清理 users 表中重複的郵箱帳號，為 user_email 唯一索引做準備。
 *
 * 處理規則：
 *  1. 空字符串郵箱統一為 NULL（NULL 不參與唯一索引衝突）
 *  2. 同一個郵箱（忽略大小寫/首尾空格）有多條時，保留一條，其餘軟刪除
 *     - 保留優先級：user_status=1 的記錄 > user_id 最大的記錄
 *     - 被刪除記錄的金幣/鑽石/積分合併到保留的那條，避免用戶資產丟失
 *     - 被刪除記錄 user_status 置 -1、user_email 置 NULL，不物理刪除，保留訂單等關聯數據
 *
 * 默認只打印重複情況，不修改數據；確認後加 --force 才實際執行。
 * 用法：php artisan users:dedupe-email
 *      php artisan users:dedupe-email --force
 */
class DedupeUserEmails extends Command
{
    protected $signature = 'users:dedupe-email {--force : 實際執行清理（默認僅列出重複情況）}';

    protected $description = '清理 users 表中重複的郵箱帳號；加 --force 才會真正寫入';

    public function handle(): int
    {
        $force = (bool)$this->option('force');
        $now = date('Y-m-d H:i:s');

        // 1) 空郵箱統一為 NULL
        $emptyCount = DB::table('users')->where('user_email', '')->count();
        $this->info("空字符串郵箱記錄：{$emptyCount} 條");
        if ($force && $emptyCount > 0) {
            DB::table('users')->where('user_email', '')->update(['user_email' => null]);
            $this->info('已將空字符串郵箱統一為 NULL');
        }

        // 2) 找重複郵箱（與唯一索引判定一致：忽略大小寫與首尾空格）
        $duplicates = DB::table('users')
            ->selectRaw('LOWER(TRIM(user_email)) as email_lower, COUNT(*) as total')
            ->whereNotNull('user_email')
            ->where('user_email', '<>', '')
            ->groupBy('email_lower')
            ->havingRaw('total > 1')
            ->get();

        if ($duplicates->isEmpty()) {
            $this->info('沒有重複郵箱，可以直接建立 user_email 唯一索引');
            return 0;
        }

        $this->warn('發現 ' . $duplicates->count() . ' 個重複郵箱：');

        $mergedGroups = 0;
        foreach ($duplicates as $duplicate) {
            $rows = DB::table('users')
                ->whereRaw('LOWER(TRIM(user_email)) = ?', [$duplicate->email_lower])
                ->orderByDesc('user_id')
                ->get();

            // 保留優先級：先找已驗證正常的記錄，沒有則保留 user_id 最大的一條
            $keeper = null;
            foreach ($rows as $row) {
                if ((int)$row->user_status === 1) {
                    $keeper = $row;
                    break;
                }
            }
            if (empty($keeper)) {
                $keeper = $rows->first();
            }

            $others = $rows->filter(function ($row) use ($keeper) {
                return (int)$row->user_id !== (int)$keeper->user_id;
            });

            $this->line(sprintf(
                '  %s：共 %d 條，保留 user_id=%d，處理 %d 條（%s）',
                $duplicate->email_lower,
                $rows->count(),
                $keeper->user_id,
                $others->count(),
                $others->pluck('user_id')->implode(', ')
            ));

            if (!$force) {
                continue;
            }

            DB::transaction(function () use ($keeper, $others, $now) {
                $sumBalance = (float)$keeper->user_balance;
                $sumCoin = (float)$keeper->user_coin;
                $sumIntegral = (int)$keeper->user_integral;
                $sumIntegralAll = (int)$keeper->user_integral_all;
                foreach ($others as $other) {
                    $sumBalance += (float)$other->user_balance;
                    $sumCoin += (float)$other->user_coin;
                    $sumIntegral += (int)$other->user_integral;
                    $sumIntegralAll += (int)$other->user_integral_all;
                }

                DB::table('users')->where('user_id', $keeper->user_id)->update([
                    'user_balance'     => $sumBalance,
                    'user_coin'        => $sumCoin,
                    'user_integral'    => $sumIntegral,
                    'user_integral_all' => $sumIntegralAll,
                    'updated_at'       => $now,
                ]);

                foreach ($others as $other) {
                    DB::table('users')->where('user_id', $other->user_id)->update([
                        'user_status' => -1,
                        'user_email'  => null,
                        'updated_at'  => $now,
                    ]);
                }
            });

            $mergedGroups++;
        }

        if (!$force) {
            $this->warn('以上為預覽，未修改任何數據。確認無誤後請執行：php artisan users:dedupe-email --force');
            return 0;
        }

        $this->info("已處理 {$mergedGroups} 個重複郵箱，可以建立 user_email 唯一索引了");
        return 0;
    }
}
