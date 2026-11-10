<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class InvitationCodeUsageLog extends Model
{
    protected $table = 'invitation_code_usage_logs';
    protected $primaryKey = 'log_id';
    public $timestamps = true;

    protected $fillable = [
        'invitation_id', 'code', 'user_id', 'reward_coin', 'reward_diamond', 'used_at'
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    /**
     * 後台-邀請碼使用記錄列表（誰在什麼時候用了哪個邀請碼、拿了多少獎勵）
     * @param array $data
     * @return array
     */
    public function getListWeb($data)
    {
        $limit = empty($data['limit']) ? 10 : $data['limit'];
        $page = ($data['page'] - 1) * $limit;
        $time = [];

        $where = [];
        if (!empty($data['code'])) {
            $where[] = array('invitation_code_usage_logs.code', 'like', '%' . $data['code'] . '%');
        }
        if (!empty($data['user_id'])) {
            $where[] = array('invitation_code_usage_logs.user_id', '=', (int)$data['user_id']);
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
            return $this->join('users', 'users.user_id', '=', 'invitation_code_usage_logs.user_id')
                ->where($where)
                ->where(function ($query) use ($time) {
                    if (!empty($time) && $time['start_time'] != '') {
                        return $query->whereBetween('invitation_code_usage_logs.used_at', $time);
                    }
                });
        };

        $datas['data'] = $build()
            ->select(
                'invitation_code_usage_logs.log_id',
                'invitation_code_usage_logs.invitation_id',
                'invitation_code_usage_logs.code',
                'invitation_code_usage_logs.user_id',
                'invitation_code_usage_logs.reward_coin',
                'invitation_code_usage_logs.reward_diamond',
                'invitation_code_usage_logs.used_at',
                'users.user_name',
                'users.user_email',
                'users.user_phone',
                'users.user_balance',
                'users.user_coin'
            )
            ->orderBy('invitation_code_usage_logs.log_id', 'desc')
            ->offset($page)->limit($limit)
            ->get()->toArray();

        $datas['count'] = $build()->count();
        $datas['total_coin'] = number_format((float)$build()->sum('invitation_code_usage_logs.reward_coin'), 2, '.', '');
        $datas['total_diamond'] = number_format((float)$build()->sum('invitation_code_usage_logs.reward_diamond'), 2, '.', '');
        return $datas;
    }
}
