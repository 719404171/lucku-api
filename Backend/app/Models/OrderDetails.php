<?php
/*
 * File: OrderDetails.php
 * Project: NewDemo
 * Created Time: 2024-07-09 10:32:55 am
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2026-07-02 5:06:56 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2024 成都睿創科技有限公司
 */


namespace App\Models;

use App\Http\Controllers\Api\MailController;
use App\Http\Controllers\Controller;
use DateTimeInterface;
use App\Models\Base;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;


/**
 * App\Models\OrderDetails
 *
 */

class OrderDetails extends Base{

    protected $table = 'order_details';
    use Notifiable;
    public $timestamps = true;
    public static $modify = false;
    /**
     * 为数组 / JSON 序列化准备日期。
     */
    protected function serializeDate(DateTimeInterface $date){
        return $date->format($this->dateFormat ?: 'Y-m-d H:i:s');
    }

    /**
     * 關聯訂單物流表
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function OrderLogistics(){
        return $this->hasMany('App\Models\OrderLogistics', 'order_id', 'order_id');
    }

    /**
     * 關聯訂單快照表
     */
    public function OrderSnapshot(){
        return $this->hasMany('App\Models\OrderSnapshot', 'details_id', 'details_id');
    }

    /**
     * 关联product表
     */
    public function product(){
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * 函數構造
     */
    public function __construct(){
        $this->language = app('global_language') == "en" ? "en_" : "";
    }

    /**
     * 修改器（修改訂單詳情規格信息）
     * @param $data
     * @return string
     */
    public function getProductSpecsAttribute($data){
        $this->language = app('global_language') == "en" ? "en_" : "";
        if (!self::$modify || empty($data)) {
            return $data;
        }

        $productSpecs = is_string($data) ? json_decode($data, true) : $data;
        if (empty($productSpecs) || !is_array($productSpecs)) {
            return $data;
        }

        $langKey = $this->language . 'product_specs_value';
        $specValues = [];

        if (isset($productSpecs[$langKey])) {
            $specValues[] = $productSpecs[$langKey] ?? '';
        } else {
            foreach ($productSpecs as $spec) {
                if (!is_array($spec)) {
                    continue;
                }
                if (isset($spec[$langKey])) {
                    $specValues[] = $spec[$langKey] ?? '';
                } elseif (isset($spec['product_specs_value']) || isset($spec['en_product_specs_value'])) {
                    $specValues[] = $spec[$langKey] ?? ($spec['product_specs_value'] ?? ($spec['en_product_specs_value'] ?? ''));
                }
            }
        }

        $specValues = array_values(array_filter($specValues, static function ($v) {
            return $v !== null && $v !== '';
        }));

        return !empty($specValues) ? implode('/', $specValues) : $data;
    }



/*********************************************************全局作用域注册**********************************************************************/
    protected static function boot(){
       parent::boot();
	   static::addGlobalScope('order_details', function (Builder $builder) {
		  return $builder->where('order_details.snapshot_status', '<>', -1);
	   });
    }

/*********************************************************admin**********************************************************************/

    /**
     * 确认完成/確認收貨
     * @param $data
     * @return array
     */
    public function confirmReceiptWeb($data,$token){
        DB::beginTransaction();
        $admin = Admins::where('token',$token)->select('admin_id','username')->first();
        $logisticsUpdate = true;
        if($data['product_type'] == 1){
            $logisticsUpdate = OrderLogistics::where('details_id',$data['details_id'])->update(['logistics_status'=>2]);
        }
        $detailsUpdate = $this->where('details_id',$data['details_id'])->update(['logistics_status'=>2]);
        if($logisticsUpdate && $detailsUpdate){
            $orderUpdate = true;
            $orderID = $this->where('details_id',$data['details_id'])->select('order_id')->first()->order_id;
            $hasOrder = $this->where('order_id',$orderID)->where('logistics_status','<',2)->get()->toArray();
            if(empty($hasOrder)){
                $orderUpdate = Order::where('order_id',$orderID)->update(['payment_status'=>5]);
                $info = Order::where('order_id',$orderID)->select('order_no','delivery_mail')->first();
                //發送訂單郵箱和推送
                $cn_content = '訂單：'.$info['order_no'].',已完成！';
                $en_content = 'Order:'.$info['order_no'].',Completed！';
                $email=new MailController();
                $EmailInfo['Email']=$info['delivery_mail'];
                //語言狀態判斷
                if($this->language == "en" ){
                    $languagenow = 'en';//英文模式
                    $EmailInfo['Message']=$en_content;
                    @self::pushOrderMessage($orderID,$cn_content,$en_content,$languagenow);
                }else{
                    $languagenow = 'cn';
                    $EmailInfo['Message']=$cn_content;
                    @self::pushOrderMessage($orderID,$cn_content,$en_content,$languagenow);
                }
                $email->MessageEmails($EmailInfo);
                $configInfo = [//增加訂單操作日誌
                    'admin_name' => $admin['username'],
                    'admin_id' => $admin['admin_id'],
                    'order_id' => $orderID,
                    'activity_type' => 8,//确认完成/確認收貨
                    'activity_value' => $cn_content,
                    'en_activity_value' => $en_content,
                    'created_at' =>  self::getDate()
                ];
                $configInsert = ActivityLog::insert($configInfo);
            }
            if($orderUpdate){
                DB::commit();
                return $this->success(__('web.edit_success'));
            }else{
                DB::rollBack();
                return $this->error(__('web.edit_fail'));
            }
        }else{
            DB::rollBack();
            return $this->error(__('web.edit_fail'));
        }
    }

/**********************************************************api**********************************************************************/




/**************************************************common(api和admin共用)***********************************************************/





/**************************************************end***********************************************************/







}
