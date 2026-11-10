<?php
/*
 * File: ShopGeTui.php
 * Created Time: 2024-05-29 11:27:52 am
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-07-15 3:29:49 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2024 成都睿創科技有限公司
 */


namespace App\Models;

use DateTimeInterface;
use App\Models\Base;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
/**
 * App\Models\ShopGeTui
 */
class ShopGeTui extends Base{


    protected $table = 'shop_getui';
    use Notifiable;
    public $timestamps = true;
    /**
     * 为数组 / JSON 序列化准备日期。
     */
    protected function serializeDate(DateTimeInterface $date)
    {
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

	   static::addGlobalScope('shop_getui', function (Builder $builder) {
		  return $builder->where('shop_getui.getui_status', '<>', -1);
	   });
    }



/**********************************************************admin**********************************************************************/


    /**
     * 添加/編輯推送配置
     * @param $data
     * @return array
     */
    public function handleShopGeTuiWeb($data){
        if(empty($data['getui_id'])){
            $data['created_at'] = self::getDate();
        }
        $datas = $this->updateOrInsert(['getui_id'=>1], $data);
        if($datas) {
            return $this->success(__('web.set_success'));
        }
    }


/**********************************************************api**********************************************************************/



/**************************************************common(api和admin共用)***********************************************************/

}
