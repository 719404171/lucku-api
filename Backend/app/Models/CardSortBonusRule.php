<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CardSortBonusRule extends Model
{
    protected $table = 'card_sort_bonus_rule';
    protected $primaryKey = 'rule_id';
    public $timestamps = true;

    protected $fillable = [
        'card_sort_id', 'trigger_at', 'card_id', 'is_active', 'sort'
    ];

    protected $casts = [
        'is_active' => 'integer',
    ];

    /**
     * 取某卡池所有啟用規則，按 trigger_at 升序
     * @return \Illuminate\Support\Collection
     */
    public static function activeBySort(int $cardSortId)
    {
        return self::where('card_sort_id', $cardSortId)
            ->where('is_active', 1)
            ->orderBy('trigger_at', 'asc')
            ->get();
    }
}
