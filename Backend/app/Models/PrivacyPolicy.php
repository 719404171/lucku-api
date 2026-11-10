<?php

namespace App\Models;

use DateTimeInterface;
use App\Models\Base;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;

/**
 * App\Models\PrivacyPolicy
 */
class PrivacyPolicy extends Base
{
    use Notifiable;
    protected $table = 'privacy_policy';
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

        static::addGlobalScope('privacy_policy', function (Builder $builder) {
            return $builder->where('privacy_policy.pp_status', '<>', -1);
        });
    }

/*********************************************************admin**********************************************************************/
    /**
     * 修改隐私政策
     * @param $data
     * @return array
     */
    public function editPrivacyPolicyWeb($data){
        $data['pp_id'] = $data['pp_id'] ?? 1;
        if(empty($data['pp_id'])){
            $data['created_at'] = self::getDate();
        }
        $datas = $this->updateOrInsert(['pp_id'=>$data['pp_id']], $data);
        if($datas){
            return $this->success(__('web.set_success'));
        }else{
            return $this->error(__('web.set_fail'));
        }
    }

/**********************************************************api**********************************************************************/

    /**
     * 獲取隐私政策
     * @return array
     */
    public function getPrivacyPolicyApi(){
        $results = $this->where('pp_status', 1)
            ->select('pp_id', $this->language.'pp_content as pp_content', 'created_at', 'updated_at')
            ->orderBy('pp_id', 'desc')
            ->first();
        if(!empty($results)){
            return $this->success(__('api.list_success'), $results);
        }else{
            return $this->success(__('api.list_null'), $results);
        }
    }
}
