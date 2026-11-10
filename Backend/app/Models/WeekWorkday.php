<?php
/*
 * File: WeekWorkday.php
 * Project: NewDemo
 * Created Time: 2024-05-30 04:45:42 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-06-24 9:36:43 am
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2024 成都睿創科技有限公司
 */

namespace App\Models;

use DateTimeInterface;
use App\Models\Base;
use Illuminate\Support\Facades\DB;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class WeekWorkday extends Base{


    use Notifiable;

    protected $table = 'week_workday';
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
	   static::addGlobalScope('week_workday', function (Builder $builder) {
		  return $builder->where('week_workday.weekday_status', '<>', -1);
	   });
    }

/*********************************************************admin**********************************************************************/

    /**
     * 獲取店鋪開店時間
     * @return array
     */
    public static function getworkday(){
        $effects=array();
        $WeekArray=array("星期日","星期一","星期二","星期三","星期四","星期五","星期六");
        // 只需全部記錄，不用分頁，避免多餘的 count(*) 查詢
        $effects = WeekWorkday::select('*')->orderBy('created_at','desc')->get()->toArray();
        foreach ($effects as $key => $value) {
            $effects[$key]['WeekdayName']=$WeekArray[$effects[$key]['weekday']];
        }
        return $effects;
    }

    /**
     * @param $data
     * @return mixed
     * 更新店鋪開店時間
     */


/**********************************************************api**********************************************************************/

    /**
     * @param $data
     * @return mixed
     * 獲取店鋪開店時間
     */
    public function getworkdayApi(){
        $effects=array();
        $WeekArray=array("星期日","星期一","星期二","星期三","星期四","星期五","星期六");
        // 只需全部記錄，不用分頁，避免多餘的 count(*) 查詢
        $effects = $this->select('*')->orderBy('created_at','desc')->get()->toArray();
        foreach ($effects as $key => $value) {
            $effects[$key]['WeekdayName']=$WeekArray[$effects[$key]['weekday']];
        }
        return $effects;
    }

/**************************************************common(api和admin共用)***********************************************************/





}
