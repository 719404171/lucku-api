<?php
/*
 * File: CardOrderLogistics.php
 * Project: NewDemo
 * Created Time: 2026-06-04 04:11:11 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2026-07-02 4:31:57 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2026 成都睿創科技有限公司
 */
namespace App\Models;

use DateTimeInterface;
use App\Models\Base;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;

class CardOrderLogistics extends Base{

    protected $table = 'order_logistics';
    protected $primaryKey = 'logistics_id';
    use Notifiable;
    public $timestamps = true;
    protected $appends = ['card_order_logistics_id'];

    public function getCardOrderLogisticsIdAttribute(){
        return $this->attributes['logistics_id'] ?? 0;
    }

    protected function serializeDate(DateTimeInterface $date){
        return $date->format($this->dateFormat ?: 'Y-m-d H:i:s');
    }

    public function __construct(){
        $this->language = app('global_language') == "en" ? "en_" : "";
    }

    protected static function boot(){
       parent::boot();
       static::addGlobalScope('order_logistics', function (Builder $builder) {
          return $builder->where('order_logistics.logistics_status', '<>', -1);
       });
    }

    /*********************************************************admin**********************************************************************/

    public function addCardOrderLogisticsWeb($data){
        DB::beginTransaction();
        try {
            // 軟刪除舊物流記錄
            $this->where('order_id', $data['order_id'])->update(['logistics_status' => -1]);

            // 新增物流記錄
            $logisticsData = [
                'order_id'         => $data['order_id'],
                'logistics_number' => $data['logistics_number'],
                'logistics_status' => 1,
                'created_at'       => date('Y-m-d H:i:s'),
                'updated_at'       => date('Y-m-d H:i:s'),
            ];
            $logiId = $this->insertGetId($logisticsData);
            if (empty($logiId)) {
                DB::rollBack();
                return $this->error(__('web.network_error'));
            }

            // 更新卡牌訂單物流狀態
            CardOrder::where('order_id', $data['order_id'])->update(['logistics_status' => 1]);
            // 更新訂單詳情物流狀態（用於篩選條件）
            CardOrderDetails::where('order_id', $data['order_id'])->update(['logistics_status' => 1]);

            DB::commit();
            return $this->success(__('web.success'));
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error(__('web.network_error'));
        }
    }

    /**********************************************************api**********************************************************************/

    public function addOrderLogistics($user_id , $details_id){
        DB::beginTransaction();
        try {
            $data['user_id'] = $user_id;
            $data['details_id'] = $details_id;
            $data['logistics_status'] = 1;
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
}
