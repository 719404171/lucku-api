<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 卡池品牌：後台「卡池管理」可為每個卡池手動維護多個品牌（存 JSON 陣列）。
 * APP 端 /api/getCardBrands 返回全部卡池品牌，/api/getCardSortByBrand 依品牌篩選卡池。
 * 上線時把每個卡池現有卡牌的 card_brand 去重預填，避免品牌欄為空。
 */
return new class extends Migration {

    public function up(): void
    {
        try {
            if (!Schema::hasColumn('card_sort', 'card_brands')) {
                Schema::table('card_sort', function (Blueprint $table) {
                    $table->text('card_brands')->nullable()->comment('卡池品牌 JSON 陣列（可多個）')->after('card_tag_order');
                });
            }

            // 預填：把卡池內卡牌的 card_brand 去重寫入；已有值的卡池不覆蓋
            $rows = DB::table('card')
                ->whereNotNull('card_brand')->where('card_brand', '<>', '')
                ->where('card_status', '<>', -1)
                ->whereNotNull('card_sort_id')
                ->select('card_sort_id', 'card_brand')
                ->distinct()->get();

            $map = [];
            foreach ($rows as $row) {
                $sortId = (int)$row->card_sort_id;
                $brand = trim((string)$row->card_brand);
                if ($sortId <= 0 || $brand === '') {
                    continue;
                }
                $map[$sortId][$brand] = 1;
            }

            foreach ($map as $sortId => $brands) {
                $hasValue = DB::table('card_sort')->where('card_sort_id', $sortId)
                    ->whereNotNull('card_brands')->where('card_brands', '<>', '')->exists();
                if ($hasValue) {
                    continue;
                }
                $list = array_keys($brands);
                sort($list);
                DB::table('card_sort')->where('card_sort_id', $sortId)->update([
                    'card_brands' => json_encode($list, JSON_UNESCAPED_UNICODE),
                ]);
            }
        } catch (\Throwable $e) {
            // 表不存在等場景直接放過
        }
    }

    public function down(): void
    {
        try {
            if (Schema::hasColumn('card_sort', 'card_brands')) {
                Schema::table('card_sort', function (Blueprint $table) {
                    $table->dropColumn('card_brands');
                });
            }
        } catch (\Throwable $e) {
            // 忽略
        }
    }
};
