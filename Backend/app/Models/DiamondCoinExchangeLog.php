<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * 鑽石兌換金幣記錄（diamond_coin_exchange_log）
 */
class DiamondCoinExchangeLog extends Model
{
    protected $table = 'diamond_coin_exchange_log';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'user_id', 'diamond_amount', 'coin_amount', 'rate'
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    /**
     * 後台-金幣兌換記錄列表（誰在什麼時候用多少鑽石換了多少金幣）
     * @param array $data
     * @return array
     */
    public function getListWeb($data)
    {
        $limit = empty($data['limit']) ? 10 : $data['limit'];
        $page = ($data['page'] - 1) * $limit;
        $time = [];

        $where = [];
        if (!empty($data['user_id'])) {
            $where[] = array('diamond_coin_exchange_log.user_id', '=', (int)$data['user_id']);
        }
        if (!empty($data['user_name'])) {
            $where[] = array('users.user_name', 'like', '%' . $data['user_name'] . '%');
        }
        if (!empty($data['user_email'])) {
            $where[] = array('users.user_email', 'like', '%' . $data['user_email'] . '%');
        }
        if (!empty($data['time'])) {
            $time['start_time'] = $data['time'][0];
            $time['end_time'] = $data['time'][1];
        }

        // 每次調用獨立構造查詢，避免 data / count / sum 共用同一 builder 互相污染
        $build = function () use ($where, $time) {
            return $this->join('users', 'users.user_id', '=', 'diamond_coin_exchange_log.user_id')
                ->where($where)
                ->where(function ($query) use ($time) {
                    if (!empty($time) && $time['start_time'] != '') {
                        return $query->whereBetween('diamond_coin_exchange_log.created_at', $time);
                    }
                });
        };

        $datas['data'] = $build()
            ->select(
                'diamond_coin_exchange_log.id',
                'diamond_coin_exchange_log.user_id',
                'diamond_coin_exchange_log.diamond_amount',
                'diamond_coin_exchange_log.coin_amount',
                'diamond_coin_exchange_log.rate',
                'diamond_coin_exchange_log.created_at',
                'users.user_name',
                'users.user_email',
                'users.user_phone',
                'users.user_balance',
                'users.user_coin'
            )
            ->orderBy('diamond_coin_exchange_log.id', 'desc')
            ->offset($page)->limit($limit)
            ->get()->toArray();

        $datas['count'] = $build()->count();
        $datas['total_diamond'] = number_format((float)$build()->sum('diamond_coin_exchange_log.diamond_amount'), 2, '.', '');
        $datas['total_coin'] = number_format((float)$build()->sum('diamond_coin_exchange_log.coin_amount'), 2, '.', '');
        return $datas;
    }
}
