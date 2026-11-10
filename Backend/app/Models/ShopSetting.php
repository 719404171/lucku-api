<?php
/*
 * File: ShopSetting.php
 * Project: NewDemo
 * Created Time: 2024-05-30 04:45:48 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2026-06-16 3:11:35 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2024 成都睿創科技有限公司
 */

namespace App\Models;

use DateTimeInterface;
use App\Models\Base;
use App\Models\User;
use App\Models\WeekWorkday;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;


/**
 * App\Models\ShopSetting
 *
 */
class ShopSetting extends Base{

    protected $table = 'shop_setting';
    use Notifiable;
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
	   static::addGlobalScope('shop_setting', function (Builder $builder) {
		  return $builder->where('shop_setting.shop_status', '<>', -1);
	   });
    }


/*********************************************************admin**********************************************************************/
    /**
     * 編輯店鋪信息
     * @param $data
     * @return array
     */
    public function editShopSettingWeb($data){
        DB::beginTransaction();
        $workday = $data['workday'] ?? null;
        unset($data['workday']);
        if(empty($data['shop_id'])){
            $data['created_at'] = self::getDate();
        }
        $res = $this->updateOrInsert(['shop_id'=>$data['shop_id']], $data);
        $update = true;
        // 同步修改用戶id = 1的信息（僅當有 shop_mail 等欄位時才更新）
        if (isset($data['shop_mail']) || isset($data['shop_phone']) || isset($data['shop_logo'])) {
            $UpdateUser = User::where('user_id', 1)->update([
                'user_name' => '線下會員',
                'user_email' => $data['shop_mail'] ?? '',
                'user_phone' => $data['shop_phone'] ?? '',
                'user_avatar' => $data['shop_logo'] ?? ''
            ]);
        }
        if ($workday) {
            foreach ($workday as $business_hour) {
                // 更新數據庫week_workday表
                $weekWorkday = WeekWorkday::where('weekday', $business_hour['weekday'])
                    ->update([
                        'start_time' => $business_hour['start_time'],
                        'end_time' => $business_hour['end_time'],
                        'is_open' => $business_hour['is_open'],
                    ]);
                if(!$weekWorkday){
                    $update = false;
                    break;
                }
            }
        }
        if($res && $update){
            DB::commit();
            return $this->success(__('web.set_success'));
        }else{
            DB::rollBack();
            return $this->error(__('web.set_fail'));
        }
    }



/**********************************************************api**********************************************************************/

    /**
     * @param $data
     * @param $language
     * @return mixed
     * 獲取店鋪預約信息
     */
    public function getShopSettingApi($language){
        $where[] = array('shop_status','=',1);
        $results = $this->where($where)->orderBy('shop_id','desc')->first();
        if(!empty($results)){
        $weekWorkday = new WeekWorkday();
        $results['opening_hours'] = $weekWorkday->getworkdayApi();
        // 根据语言选择地址字段
        if ($language == 'en_') {
            // 如果是英文模式且有英文地址字段，使用英文地址
            $results['shop_address'] = $results['en_shop_address'];
        } else {
            // 默认使用中文地址或原始地址
            $results['shop_address'] = $results['shop_address'] ?? '';
        }
        //移除返回内容中的英文字段
        unset($results['en_shop_address']);
        }
        if(!empty($results)){
            return $this->success( __('api.list_success'), $results);
        }else{
            return $this->success( __('api.list_null'), $results);
        }
    }

/**************************************************common(api和admin共用)***********************************************************/


}
