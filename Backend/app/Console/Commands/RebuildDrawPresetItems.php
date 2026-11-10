<?php

namespace App\Console\Commands;

use App\Models\Card;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * 重建抽卡預設順序表 draw_preset_items。
 * 用於功能上線後把既有 draw_preset.preset_data（大 JSON）一次性導入正規化順序表；
 * 之後管理端保存預設會自動同步，無需再手動執行。
 *
 * 用法：
 *   php artisan draw-preset:rebuild                 # 重建全部啟用中的預設
 *   php artisan draw-preset:rebuild 13              # 只重建卡池 13
 *   php artisan draw-preset:rebuild --if-needed     # 僅在順序表為空時重建（供定時預熱，開銷極低）
 */
class RebuildDrawPresetItems extends Command
{
    protected $signature = 'draw-preset:rebuild {sort_id?} {--if-needed : 僅在順序表為空時才重建}';

    protected $description = '重建抽卡預設順序表（draw_preset_items）';

    public function handle(): int
    {
        $sortId = $this->argument('sort_id');
        $onlyIfNeeded = (bool)$this->option('if-needed');
        $card = new Card();

        if (!empty($sortId)) {
            $sortIds = [(int)$sortId];
        } else {
            $sortIds = DB::table('draw_preset')
                ->where('is_active', 1)
                ->pluck('card_sort_id')
                ->all();
        }

        if (empty($sortIds)) {
            $this->info('no active preset');
            return 0;
        }

        foreach ($sortIds as $id) {
            $id = (int)$id;
            if ($onlyIfNeeded) {
                $hasItems = DB::table('draw_preset_items')->where('card_sort_id', $id)->exists();
                if ($hasItems) {
                    continue;
                }
            }
            $count = $card->rebuildPresetItems($id);
            $this->info("card_sort_id={$id} rebuilt items: {$count}");
        }

        return 0;
    }
}
