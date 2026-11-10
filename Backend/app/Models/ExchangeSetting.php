<?php

namespace App\Models;

use DateTimeInterface;
use App\Models\Base;
use Illuminate\Support\Facades\DB;
use Illuminate\Notifications\Notifiable;

class ExchangeSetting extends Base
{
    use Notifiable;
    protected $table = 'exchange_settings';
    protected $primaryKey = 'exchange_id';
    public $timestamps = true;

    protected function serializeDate(DateTimeInterface $date){
        return $date->format($this->dateFormat ?: 'Y-m-d H:i:s');
    }

    public function getExchangeListWeb($data)
    {
        $limit = $data['limit'];
        $page = ($data['page'] - 1) * $limit;
        $where[] = array('status', '<>', -1);
        if (isset($data['status']) && (!empty($data['status']) || is_numeric($data['status']))) {
            $where['status'] = $data['status'];
        }
        $datas['data'] = $this->where($where)
            ->offset($page)->limit($limit)
            ->orderBy('sort', 'asc')
            ->orderBy('exchange_id', 'desc')
            ->get()->toArray();
        $datas['count'] = $this->where($where)->count();
        return $datas;
    }

    public function handleExchangeWeb($data)
    {
        $where[] = array('status', '<>', -1);
        if (empty($data['exchange_id'])) {
            $data['created_at'] = self::getDate();
            $res = $this->insert($data);
        } else {
            $where[] = array('exchange_id', $data['exchange_id']);
            $res = $this->where($where)->update($data);
        }
        return $res ? self::success(__('web.success')) : self::error(__('web.fail'));
    }

    public function deleteExchangeWeb($exchangeId)
    {
        $res = $this->where('exchange_id', $exchangeId)->update(['status' => -1]);
        return $res ? self::success(__('web.success')) : self::error(__('web.fail'));
    }
}
