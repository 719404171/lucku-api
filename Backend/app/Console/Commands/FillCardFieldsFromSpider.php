<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FillCardFieldsFromSpider extends Command
{
    protected $signature = 'card:fill-from-spider
                            {--chunk=200 : 每批處理筆數}
                            {--dry-run : 僅顯示將更新的數據，不實際執行}';

    protected $description = '從 spider_cards 表填充 card 表的 crawler_id, crawler_url, card_category, deal_condition 字段';

    public function handle()
    {
        $chunkSize = (int)$this->option('chunk');
        $dryRun = $this->option('dry-run');
        $totalUpdated = 0;
        $totalSkipped = 0;

        $this->info('開始填充 card 表缺少的字段...');
        if ($dryRun) {
            $this->warn('【乾執行模式】僅顯示，不實際更新');
        }

        // 分批處理，避免 OOM
        DB::table('card')
            ->where(function ($q) {
                $q->whereNull('crawler_id')
                  ->orWhereNull('crawler_url')
                  ->orWhereNull('card_category')
                  ->orWhereNull('deal_condition')
                  ->orWhere('crawler_id', '')
                  ->orWhere('crawler_url', '')
                  ->orWhere('card_category', '')
                  ->orWhere('deal_condition', '');
            })
            ->orderBy('card_id')
            ->chunk($chunkSize, function ($cards) use ($dryRun, &$totalUpdated, &$totalSkipped) {
                foreach ($cards as $card) {
                    // 以 trading_card_id 或 card_name/card_img 關聯 spider_cards
                    $spider = null;
                    if (!empty($card->trading_card_id)) {
                        $spider = DB::table('spider_cards')->where('id', $card->trading_card_id)->first();
                    }
                    if (!$spider && !empty($card->card_name)) {
                        $spider = DB::table('spider_cards')
                            ->where('card_name', $card->card_name)
                            ->orderBy('id', 'desc')
                            ->first();
                    }

                    if (!$spider) {
                        $totalSkipped++;
                        continue;
                    }

                    $update = [];
                    if (empty($card->crawler_id) && !empty($spider->crawler_id)) {
                        $update['crawler_id'] = $spider->crawler_id;
                    }
                    if (empty($card->crawler_url) && !empty($spider->crawler_url)) {
                        $update['crawler_url'] = $spider->crawler_url;
                    }
                    if (empty($card->card_category) && !empty($spider->card_category)) {
                        $update['card_category'] = $spider->card_category;
                    }
                    if (empty($card->deal_condition) && !empty($spider->deal_condition)) {
                        $update['deal_condition'] = $spider->deal_condition;
                    }

                    if (empty($update)) {
                        $totalSkipped++;
                        continue;
                    }

                    $update['updated_at'] = now();

                    if ($dryRun) {
                        $this->line("  [card_id={$card->card_id}] 將更新: " . json_encode($update, JSON_UNESCAPED_UNICODE));
                    } else {
                        DB::table('card')->where('card_id', $card->card_id)->update($update);
                    }
                    $totalUpdated++;
                }

                $this->info("  已處理 {$totalUpdated} 筆...");
            });

        $this->info("完成！更新 {$totalUpdated} 筆，跳過 {$totalSkipped} 筆。");
    }
}
