<?php

namespace App\Console\Commands;

use App\Models\CardSort;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 按公式自動更新卡池抽卡價格
 *
 * 公式：抽卡價格（取整）= 卡牌總價 ÷ 卡牌數量 × 手動百分比 ÷ 100
 * 僅更新 card_sort.auto_update_price = 1 的卡池；每個卡池兩分鐘內只會更新一次（冪等）。
 *
 * 執行時間由 app_settings 決定（實際在設定時間點延遲5分鐘後執行）：
 *   auto_update_enabled : 總開關（true/false，預設 true）
 *   auto_update_time1   : 每日第一個更新時間（如 05:00，實際 05:05 執行）
 *   auto_update_time2   : 每日第二個更新時間（如 18:00，實際 18:05 執行）
 *
 * 用法：
 *   php artisan cards:update-draw-price               # 定時入口：僅在設定的時間點執行（由 schedule 每分鐘觸發）
 *   php artisan cards:update-draw-price --force       # 立即更新所有開啟自動更新的卡池
 *   php artisan cards:update-draw-price --card_sort_id=40  # 立即重算指定卡池（忽略開關）
 */
class UpdateCardDrawPrice extends Command
{
    protected $signature = 'cards:update-draw-price
        {--force : 忽略時間設置，立即更新}
        {--card_sort_id= : 只重算指定卡池（忽略自動更新開關）}';

    protected $description = '按「卡牌總價÷卡牌數量×百分比」公式自動更新卡池抽卡價格（每日兩個時間點）';

    public function handle(): int
    {
        // 指定卡池：直接強制重算（供後台/排查使用）
        $sortId = (int)$this->option('card_sort_id');
        if ($sortId > 0) {
            $stats = CardSort::recalcAndSaveDrawPrice($sortId, true);
            if ($stats === null) {
                $this->warn("card_sort_id={$sortId} skipped");
                return 0;
            }
            $this->info(sprintf(
                'card_sort_id=%d count=%d total=%.2f percent=%.2f draw_price=%d',
                $sortId, $stats['count'], $stats['total_price'], $stats['percent'], $stats['computed_draw_price']
            ));
            return 0;
        }

        // 讀取 app_settings（key/value 表）
        $settings = [];
        if (Schema::hasTable('app_settings')) {
            $settings = DB::table('app_settings')
                ->whereIn('key', ['auto_update_enabled', 'auto_update_time1', 'auto_update_time2'])
                ->pluck('value', 'key')
                ->toArray();
        }
        $enabled = isset($settings['auto_update_enabled'])
            ? strtolower(trim((string)$settings['auto_update_enabled'])) !== 'false'
            : true;

        if (!$this->option('force') && !$enabled) {
            $this->info('auto update disabled by app_settings.auto_update_enabled');
            return 0;
        }

        // 非強制模式：當前時間必須命中設定的時間點+5分鐘（每分鐘由 schedule 觸發）
        if (!$this->option('force')) {
            $nowHi = date('H:i');
            $slots = array_values(array_filter([
                trim((string)($settings['auto_update_time1'] ?? '')),
                trim((string)($settings['auto_update_time2'] ?? '')),
            ]));
            // 每個時間點延遲5分鐘執行（如 05:00 → 05:05）
            $targets = array_map(function ($slot) {
                return date('H:i', strtotime($slot . ' +5 minutes'));
            }, $slots);
            if (!in_array($nowHi, $targets, true)) {
                // 未到更新時間，安靜退出
                return 0;
            }
        }

        $result = CardSort::autoUpdateAllPoolDrawPrices();
        $this->info(sprintf(
            'auto update finished: updated=%d skipped=%d',
            $result['updated'], $result['skipped']
        ));
        foreach ($result['items'] as $item) {
            $this->line("  card_sort_id={$item['card_sort_id']} draw_price={$item['draw_price']}");
        }

        return 0;
    }
}
