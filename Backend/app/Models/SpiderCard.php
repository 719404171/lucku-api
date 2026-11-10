<?php

namespace App\Models;

use DateTimeInterface;
use App\Models\Base;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;

class SpiderCard extends Model{

    protected $table = 'spider_cards';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected function serializeDate(DateTimeInterface $date){
        return $date->format($this->dateFormat ?: 'Y-m-d H:i:s');
    }
}
