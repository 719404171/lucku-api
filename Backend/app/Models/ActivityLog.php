<?php
/*
 * File: ActivityLog.php
 * Created Time: 2024-05-29 11:27:52 am
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2026-06-03 4:47:28 pm
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
 * App\Models\ActivityLog
 *
 */
class ActivityLog extends Base{

    protected $table = 'activity_log';


/*********************************************************全局作用域注册**********************************************************************/
    protected static function boot(){
       parent::boot();

	   static::addGlobalScope('activity_status', function (Builder $builder) {
		  return $builder->where('activity_log.activity_status', '<>', -1);
	   });
    }


    /**
     * 函數構造
     */
    public function __construct(){
        $this->language = app('global_language') == "en" ? "en_" : "";
    }



/**********************************************************admin**********************************************************************/

    /**
     * 獲取員工操作日誌
     * @param $data
     * @return array
     */
    public function getActivityLogListWeb($data){
        $limit = $data['limit'] ?? 10;
        $page = ($data['page'] ?? 1) - 1;
        $offset = $page * $limit;
        $query = ActivityLog::where('activity_status', 1);
        if (!empty($data['activity_type']) && $data['activity_type'] !== '' && $data['activity_type'] !== null) {
            // 条件查询
            $query->where('activity_type', $data['activity_type']);
        } else {
            // 默认查询
            $query->whereIn('activity_type', [1, 2, 3, 4, 5, 9, 10, 11 ,12, 13]);
            //操作类型： 管理員的操作日志
            // 1：用户积分  2：用户钱包3:庫存4：位置 5:成本 6:訂單修改預約時間
            // 7:訂單確認支付 8:訂單確認完成/確認收貨 9:訂單刪除(需要在操作日誌查看)
            // 10:新增用户 11:新增產品 12:新增綫下訂單 13:卡牌多用戶抽取檢測(查詢/詳情/導出)
        }
        if (!empty($data['admin_name'])) {
            $query->where('admin_name', 'like', '%' . $data['admin_name'] . '%');
        }
        if (!empty($data['time'])) {
            $query->whereBetween('created_at', [$data['time'][0], $data['time'][1]]);
        }
        $datas['data'] = $query->clone()
            ->offset($offset)
            ->limit($limit)
            ->latest()
            ->get()
            ->toArray();
        $datas['count'] = $query->clone()
            ->whereIn('activity_type', [1, 2, 3, 4, 5, 9, 10, 11, 12, 13])
            ->count();
        return $this->success(__('web.success'),$datas);
    }

    /**
     * 獲取員工-訂單操作日誌
     * @param $data
     * @return array
     */
    public function getOrderActivityLogListWeb($data){
        $limit = empty($data['limit']) ? 10 : $data['limit'];
        $page =  ( $data['page'] - 1 ) * $limit;
        $time = null;
        $allowedTypes = [6, 7, 8, 9];//操作类型：-訂單的專屬操作日志
        // 1：用户积分  2：用户钱包3:庫存4：位置 5:成本
        // 6:訂單修改預約時間 7:訂單確認支付 8:訂單確認完成/確認收貨 9:訂單刪除(需要在操作日誌查看)
        // 10:新增用户 11:新增產品 12:新增綫下訂單
        $query = $this->where('activity_status', '=', 1);
        if (!empty($data['admin_name'])) {
             $query->where('admin_name', 'like', '%' . $data['admin_name'] . '%');
        }
        // 类型筛选
        if (!empty($data['activity_type'])) {
            // 如果传入了特定类型，只筛选该类型
            $query->where('activity_type', '=', $data['activity_type']);
        } else {
            // 默认获取所有订单类型的参数
            $query->whereIn('activity_type', $allowedTypes);//后续有新的订单类型就在数组中加
        }
        if(!empty($data['time'])){//時間篩選
            $time['start_time'] = $data['time'][0];
            $time['end_time'] = $data['time'][1];
        }
        if ($time != null) {
        $query->whereBetween('created_at', $time);
        }
        if (!empty($data['order_id'])) {
            $query->where('order_id', '=', $data['order_id']);//订单ID筛选
        }
        $datas['data']= $query->offset($page)
            ->limit($limit)
            ->latest()
            ->get()->toArray();
        // 统计符合条件的记录
        $countQuery = $this->where('activity_status', '=', 1);
        if (!empty($data['admin_name'])) {
            $countQuery->where('admin_name', 'like', '%' . $data['admin_name'] . '%');
        }
        if (!empty($data['activity_type'])) {
            $countQuery->where('activity_type', '=', $data['activity_type']);
        } else {
            $countQuery->whereIn('activity_type', $allowedTypes);
        }
        if (!empty($data['order_id'])) {
            $countQuery->where('order_id', '=', $data['order_id']);
        }
        if ($time != null) {
            $countQuery->whereBetween('created_at', $time);
        }
        $datas['count'] = $countQuery->count();

        return $this->success(__('web.success'), $datas);
}

    /**
     * 刪除員工操作日誌
     * @param $activityID
     * @return array
     */
    public function deleteActivityLogWeb($activityID){
        $data = self::softDelete('activity_log','activity_id',$activityID,'activity_status',-1);
        if($data){
            return $this->success(__('web.delete_success'));
        }else{
            return $this->error(__('web.delete_fail'));
        }
    }


/**********************************************************api**********************************************************************/


/**************************************************common(api和admin共用)***********************************************************/

}
