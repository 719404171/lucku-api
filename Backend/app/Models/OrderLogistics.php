<?php
/*
 * File: OrderDetails.php
 * Project: NewDemo
 * Created Time: 2024-07-09 10:32:55 am
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2026-07-02 5:41:00 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2024 成都睿創科技有限公司
 */


namespace App\Models;

use DateTimeInterface;
use App\Models\Base;
use App\Models\OrderDetails;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;


/**
 * App\Models\OrderSnapshot
 *
 */

class OrderLogistics extends Base{

    protected $table = 'order_logistics';
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
	   static::addGlobalScope('order_logistics', function (Builder $builder) {
		  return $builder->where('order_logistics.logistics_status', '<>', -1);
	   });
    }

/*********************************************************admin**********************************************************************/

    /**
     * 添加訂單物流
     * @param $data
     * @return array
     */
    public function addOrderLogisticsWeb($data){
        DB::beginTransaction();
        $logisticsId = $data['logistics_id'] ?? 0;
        $detailsId = $data['details_id'] ?? 0;
        $orderId = $data['order_id'] ?? 0;

        // 如果只有 details_id，通過它查找 order_id
        if (empty($orderId) && !empty($detailsId)) {
            $detail = OrderDetails::where('details_id', $detailsId)->first(['order_id']);
            $orderId = $detail ? $detail->order_id : 0;
        }

        if (empty($orderId)) {
            DB::rollBack();
            return $this->error(__('web.network_error'));
        }

        if (empty($logisticsId)) {
            // 軟刪除舊物流記錄
            DB::table('order_logistics')->where('order_id', $orderId)->update(['logistics_status' => -1]);
            // 建立新物流記錄
            $logisticsId = DB::table('order_logistics')->insertGetId([
                'order_id' => $orderId,
                'logistics_number' => $data['logistics_number'],
                'logistics_status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            if (!$logisticsId) {
                DB::rollBack();
                return $this->error(__('web.network_error'));
            }
        } else {
            $this->where('logistics_id', $logisticsId)->update(['logistics_number' => $data['logistics_number'], 'logistics_status' => 1]);
        }

        // 更新訂單詳情物流狀態
        OrderDetails::where('order_id', $orderId)->update(['logistics_status' => 1]);

        DB::commit();
        return $this->success(__('web.success'));
    }

/**********************************************************api**********************************************************************/


/**************************************************common(api和admin共用)***********************************************************/


    /**
     * @param $token
     * @return mixed
     * 添加訂單詳情物流
     */
    public function addOrderLogistics($user_id , $order_id){
        DB::beginTransaction();
        try {
            $data['user_id'] = $user_id;
            $data['order_id'] = $order_id;
            $data['logistics_status'] = 0;
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
            $effects= $this->insert($data);
            if(!empty($effects)){
                DB::commit();   return $effects;
            }else{
                DB::rollBack();   return false;
            }
        }catch (\Exception $e) {
            DB::rollBack();   return  $e->getMessage();
        }
    }




/**************************************************end***********************************************************/







}
