<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class TradingCard extends Model{

    protected $table = 'spider_cards';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $casts = [
        'condition_prices' => 'array',
    ];

    protected function serializeDate(DateTimeInterface $date){
        return $date->format($this->dateFormat ?: 'Y-m-d H:i:s');
    }
}
