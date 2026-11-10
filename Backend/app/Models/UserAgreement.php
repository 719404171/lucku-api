<?php

namespace App\Models;

use DateTimeInterface;
use App\Models\Base;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;

/**
 * App\Models\UserAgreement
 */
class UserAgreement extends Base
{
    use Notifiable;
    protected $table = 'user_agreement';
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

        static::addGlobalScope('user_agreement', function (Builder $builder) {
            return $builder->where('user_agreement.ua_status', '<>', -1);
        });
    }

/*********************************************************admin**********************************************************************/
    /**
     * 修改用户协议
     * @param $data
     * @return array
     */
    public function editUserAgreementWeb($data){
        $data['ua_id'] = $data['ua_id'] ?? 1;
        if(empty($data['ua_id'])){
            $data['created_at'] = self::getDate();
        }
        $datas = $this->updateOrInsert(['ua_id'=>$data['ua_id']], $data);
        if($datas){
            return $this->success(__('web.set_success'));
        }else{
            return $this->error(__('web.set_fail'));
        }
    }

/**********************************************************api**********************************************************************/

    /**
     * 獲取用户协议
     * @return array
     */
    public function getUserAgreementApi(){
        $results = $this->where('ua_status', 1)
            ->select('ua_id', $this->language.'ua_content as ua_content', 'created_at', 'updated_at')
            ->orderBy('ua_id', 'desc')
            ->first();
        if(!empty($results)){
            return $this->success(__('api.list_success'), $results);
        }else{
            return $this->success(__('api.list_null'), $results);
        }
    }
}
