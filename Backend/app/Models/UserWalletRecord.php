<?php
/*
 * File: UserWalletRecord.php
 * Project: NewDemo
 * Created Time: 2024-07-03 02:25:52 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2026-06-26 5:58:08 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2024 成都睿創科技有限公司
 */


namespace App\Models;

use DateTimeInterface;
use App\Models\Base;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;


/**
 * App\Models\UserWalletRecord
 *
 * @property int $id
 * @property int|null $user_id 用户id
 * @property int|null $type 1：购买2：充值3：提现
 * @property string|null $explain 说明
 * @property float|null $money 金额
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @mixin \Eloquent
 */
class UserWalletRecord extends Base{

    /**
     * wallet_type 常量定義
     * 1 = 錢包抽卡（Wallet draw card）
     * 2 = 錢包充值（Wallet recharge）
     * 3 = 錢包提現（Wallet withdraw）
     * 4 = 钻石兑换 （diamond exchange）
     * 5 = 钻石抽奖（diamond lottery）
     * 6 = 购买商品
     */

    /**
     * payment_mode 常量定義
     * 1 = paypal支付
     * 2 = Stripe支付
     * 3 = 钱包支付
     * 4 = 钻石支付
     */

    use Notifiable;
    protected $table = 'user_wallet_record';
    protected $primaryKey = 'wastage_id';
    public $timestamps = true;
    /**
     * 为数组 / JSON 序列化准备日期。
     */
    protected function serializeDate(DateTimeInterface $date){
        return $date->format($this->dateFormat ?: 'Y-m-d H:i:s');
    }



    /**
     * 函數構造
     */
    public function __construct(){
        $this->language = app('global_language') == "en" ? "en_" : "";
    }
/*********************************************************全局作用域注册**********************************************************************/

    protected static function boot(){
       parent::boot();
	   static::addGlobalScope('user_wallet_record', function (Builder $builder) {
		  return $builder->where('user_wallet_record.wastage_status', '<>', -1);
	   });
    }
/*********************************************************admin**********************************************************************/

/**********************************************************api**********************************************************************/

    /**
     * 獲取用戶錢包明細
     * @param $data
     * @return array
     */
    public function getUserWallet($data)
    {
        $type = isset($data['type']) ? (int)$data['type'] : 1;
        $page = (int)($data['page'] ?? 1);
        $limit = (int)($data['limit'] ?? 10);
        $userId = $data['user_id'] ?? 0;

        // type=1 → 錢包記錄 (wallet_type 1,2,3 + wallet_type=6 且 payment_mode=3)
        // type=2 → 鑽石記錄 (wallet_type 4,5 + wallet_type=6 且 payment_mode=4)
        $userFields = ($type == 2) ? 'user_coin' : 'user_balance';

        $user = User::where('user_id', $userId)->select('user_id', 'user_name', 'user_phone', 'user_avatar', $userFields)->first();
        if (!$user) {
            return $this->success(__('api.list_null'), [
                'list' => [],
                'total' => 0,
                'page' => $page,
                'limit' => $limit,
                'user_coin' => 0,
                'user_balance' => 0,
            ]);
        }

        $query = $this->where('user_id', $userId)
            ->where(function ($q) use ($type) {
                if ($type == 2) {
                    // 鑽石記錄：wallet_type 4,5 或 wallet_type=6 且 payment_mode=5（DiamondPayment）
                    $q->whereIn('wallet_type', [4, 5])
                      ->orWhere(function ($q2) {
                          $q2->where('wallet_type', 6)->where('payment_mode', 5);
                      });
                } else {
                    // 錢包記錄：wallet_type 1,2,3 或 wallet_type=6 且 payment_mode=4（BalancePayment）
                    $q->whereIn('wallet_type', [1, 2, 3])
                      ->orWhere(function ($q2) {
                          $q2->where('wallet_type', 6)->where('payment_mode', 4);
                      });
                }
            });

        $total = (clone $query)->count();
        $offset = ($page - 1) * $limit;
        $list = (clone $query)
            ->orderBy('created_at', 'desc')
            ->skip($offset)
            ->take($limit)
            ->get()
            ->toArray();

        return $this->success(__('api.list_success'), [
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'user_coin' => $user->user_coin ?? 0,
            'user_balance' => $user->user_balance ?? 0,
        ]);
    }

/**************************************************common(api和admin共用)***********************************************************/
}
