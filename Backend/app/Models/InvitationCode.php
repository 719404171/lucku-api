<?php
/*
 * File: InvitationCode.php
 * Project: NewDemo
 * Created Time: 2026-06-25 05:16:21 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2026-06-26 5:05:02 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2026 成都睿創科技有限公司
 */


namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class InvitationCode extends Model
{
    protected $table = 'invitation_codes';
    protected $primaryKey = 'invitation_id';
    public $timestamps = true;

    protected $fillable = [
        'code', 'use_count', 'max_uses', 'reward_coin', 'reward_diamond', 'status'
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    /**
     * 生成唯一邀請碼
     */
    public static function generateCode($length = 8)
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        do {
            $code = '';
            for ($i = 0; $i < $length; $i++) {
                $code .= $characters[random_int(0, strlen($characters) - 1)];
            }
        } while (self::where('code', $code)->exists());

        return $code;
    }

    /**
     * 檢查是否可繼續使用
     */
    public function isAvailable()
    {
        if ($this->status != 1) return false;
        if ($this->max_uses == 0) return true; // 不限次數
        return $this->use_count < $this->max_uses;
    }
}
