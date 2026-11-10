<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/7/4
 * Time: 13:52
 */

namespace App\Models;

use Illuminate\Notifications\Notifiable;

class AdminLog extends Base
{
    use Notifiable;
    protected $table = 'admin_log';
    protected $primaryKey = 'login_id';
    public $timestamps = true;

    /**
     * 獲取管理員登錄日誌
     * @param $data
     * @return array
     */
    public function getAdminLogListWeb($data){
        $limit = $data['limit'];
        $page = ($data['page'] - 1) * $limit;
        $where = [];
        if(!empty($data['login_admin_name'])){
            $where['login_admin_name'] =  $data['login_admin_name'];
        }
        $log['data'] = $this->where($where)
            ->orderBy('login_id', 'desc')
            ->offset($page)->limit($limit)
            ->get()->toArray();
        $log['count'] = $this->where($where)->count();
        return $this->success(__('web.success'),$log);
    }

}
