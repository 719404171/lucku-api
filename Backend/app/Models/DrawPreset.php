<?php
/*
 * File: DrawPreset.php
 * Project: NewDemo
 * Created Time: 2026-05-22 08:51:15 am
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2026-05-22 9:49:00 am
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2026 成都睿創科技有限公司
 */

namespace App\Models;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class DrawPreset extends Model
{
    protected $table = 'draw_preset';
    protected $primaryKey = 'preset_id';
    /**
     * 为数组 / JSON 序列化准备日期。
     */
    protected function serializeDate(DateTimeInterface $date){
        return $date->format($this->dateFormat ?: 'Y-m-d H:i:s');
    }




    protected $fillable = [
        'card_sort_id',
        'preset_data',
        'is_active',
        'is_lock'
    ];

    protected $casts = [
        'preset_data' => 'array',
        'is_active' => 'integer',
        'is_lock' => 'integer'
    ];
}
