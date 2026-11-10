<?php

namespace App\Models;

use DateTimeInterface;
use App\Models\Base;
use Illuminate\Database\Eloquent\Builder;

class BannerPopup extends base
{
    protected $table = 'banner_popup';
    public $timestamps = true;

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format($this->dateFormat ?: 'Y-m-d H:i:s');
    }

    protected static function boot(){
        parent::boot();
        static::addGlobalScope('popup_status', function (Builder $builder) {
            return $builder->where('banner_popup.popup_status', '<>', -1);
        });
    }

/*********************************************************admin**********************************************************************/

    /**
     * 获取 Banner 弹窗单条配置（始终返回一条，没有则返回 null）
     */
    public function getBannerPopupWeb(){
        $data = $this->orderBy('popup_id', 'asc')->first();
        return $this->success(__('web.success'), $data ? $data->toArray() : null);
    }

    /**
     * 保存 Banner 弹窗配置（单例：没有则新建，有则更新）
     * 与 Banner::handleBannerListWeb 保持一致，用 updateOrInsert 绕过 fillable
     */
    public function handleBannerPopupWeb($data){
        if(empty($data['popup_id'])){
            $data['created_at'] = self::getDate();
        }
        $datas = $this->updateOrInsert(['popup_id' => $data['popup_id']], $data);
        if($datas){
            return $this->success(__('web.set_success'));
        }else{
            return $this->error(__('web.set_fail'));
        }
    }

/**********************************************************api**********************************************************************/

    /**
     * APP 端获取 Banner 弹窗：仅返回 popup_enable=1 且 popup_status=1 的单条
     */
    public function getBannerPopupApi(){
        $data = $this->where('popup_enable', 1)
            ->where('popup_status', 1)
            ->orderBy('popup_id', 'asc')
            ->first();

        if($data){
            return $this->success(__('api.list_success'), $data->toArray());
        }else{
            return $this->success(__('api.list_null'), null);
        }
    }
}
