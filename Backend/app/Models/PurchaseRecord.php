<?php
/*
 * File: PurchaseRecord.php
 * Project: NewDemo
 * Created Time: 2026-06-04 04:11:11 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2026-06-04 4:33:25 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2026 成都睿創科技有限公司
 */


namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class PurchaseRecord extends Base
{
    use Notifiable;
    protected $table = 'purchase_record';
    protected $primaryKey = 'purchase_id';
    public $timestamps = true;

    /**
     * 获取采购记录
     * @param $data
     * @param $language
     * @return mixed
     */
    public function getPurchaseRecordListWeb($data,$language){
        $limit = $data['limit'];
        $page = ($data['page'] - 1) * $limit;
        $time = [];
        $where[] = array('purchase_record.purchase_status', '<>', -1);
        if (!empty($data['product_name'])) {
            $where[] = array('product.'.$language.'product_name', 'like', '%' . $data['product_name'] . '%');
        }
        if(!empty($data['time'])){
            $time['start_time'] = $data['time'][0];
            $time['end_time'] = $data['time'][1];
        }
        $datas['data'] = $this->join('product','product.product_id','=','purchase_record.product_id')
            ->join('admins','admins.admin_id','=','purchase_record.admin_id')
            ->where($where)
            ->where(function ($query) use ($time){
                if(!empty($time) && $time['start_time'] != ''){
                    return $query->whereBetween('purchase_record.created_at',$time);
                }
            })
            ->select('purchase_record.*','product.'.$language.'product_name as product_name','admins.username')
            ->offset($page)->limit($limit)
            ->orderBy('purchase_record.created_at', 'desc')
            ->get()->toArray();
        $datas['count'] = $this->join('product','product.product_id','=','purchase_record.product_id')
            ->join('admins','admins.admin_id','=','purchase_record.admin_id')
            ->where($where)
            ->where(function ($query) use ($time){
                if(!empty($time) && $time['start_time'] != ''){
                    return $query->whereBetween('purchase_record.created_at',$time);
                }
            })->count();
        return $datas;
    }

    /**
     * 添加/编辑采购记录
     * @param $purchaseRecord
     * @return array
     */
    public function handlePurchaseRecordListWeb($purchaseRecord){
        if(empty($purchaseRecord['purchase_id'])){
            $purchaseRecord['created_at'] = self::getDate();
        }
        $data = $this->updateOrInsert(['purchase_id'=>$purchaseRecord['purchase_id']], $purchaseRecord);
        if($data){
            return $this->success(__('web.success'));
        }else{
            return $this->error(__('web.network_error'));
        }
    }

    /**
     * 删除采购记录
     * @param $purchaseRecordID
     * @return array
     */
    public function deletePurchaseRecordListWeb($purchaseRecordID){
        $data = self::softDelete('purchase_record','purchase_id',$purchaseRecordID,'purchase_status',-1);
        if($data){
            return $this->success(__('web.delete_success'));
        }else{
            return $this->error(__('web.delete_fail'));
        }
    }

    /**
     * 采购邮箱发送
     * @param $purchaseID
     * @param $language
     * @return array
     */
    public function purchaseRecordEmailWeb($purchaseID,$language){
        DB::beginTransaction();
        $purchaseRecordInfo = $this->join('product','product.product_id','=','purchase_record.product_id')
            ->join('admins','admins.admin_id','=','purchase_record.admin_id')
            ->where('purchase_record.purchase_id',$purchaseID)
            ->select('admins.username','product.'.$language.'product_name as product_name','purchase_record.product_specs','purchase_record.purchase_number','purchase_record.purchase_remarks','purchase_record.purchase_contact')
            ->first();
        $purchaseUpdate = $this->where('purchase_id', $purchaseID)->update(['purchase_send' => 1]);
        if($purchaseUpdate){
            if(empty($purchaseRecordInfo['purchase_contact'])){
                DB::rollBack();
                return $this->error(__('web.no_contact'));
            }else{
                $to = $purchaseRecordInfo['purchase_contact'];
                $subject = __('web.procurement_notice') . ' - ' . self::getDate();
                $result = \App\Services\MailService::send('emails.purchaseRecordEmail', ['purchaseInfo' => $purchaseRecordInfo], [
                    'to'      => $to,
                    'subject' => $subject,
                ]);
                if ($result['success']) {
                    DB::commit();
                    return $this->success(__('web.email_sending_success'));
                } else {
                    \Log::error('採購通知郵件發送失敗', ['to' => $to, 'error' => $result['error']]);
                    DB::rollBack();
                    return $this->error(__('web.email_sending_failed'));
                }
            }
        }else{
            DB::rollBack();
            return $this->error(__('web.email_sending_failed'));
        }
    }

    /**
     * 采购進倉/退倉
     * @param $purchaseID
     * @param $purchaseType
     * @param $token
     * @return array
     */
    public function purchaseWarehouseWeb($purchaseID, $purchaseType, $token) {
        DB::beginTransaction();
        try {
            $admin = Admins::where('token', $token)->select('admin_id', 'username')->first();
            if (!$admin) {
                return $this->error(__('web.admin_not_found'));
            }

            $purchaseInfo = $this->where('purchase_id', $purchaseID)->first();
            if (!$purchaseInfo) {
                return $this->error(__('web.purchase_not_found'));
            }

            $specsValue = '';
            if (!empty($purchaseInfo['product_relation_id'])) {
                $productvalueId = ProductRelation::where('product_relation_id', $purchaseInfo['product_relation_id'])
                    ->select('product_specs_value_id')
                    ->first();

                if ($productvalueId && $productvalueId['product_specs_value_id']) {
                    $productspecsName = ProductSpecsValue::where('product_specs_value_id', $productvalueId['product_specs_value_id'])
                        ->select('product_specs_value')
                        ->first();

                    $specsValue = $productspecsName['product_specs_value'] ?? '';
                }
            }

            $productRelation = true;
            $product = true;
            $costInsert = true;

            if (!empty($purchaseInfo['product_relation_id'])) {
                if (ProductRelation::where('product_relation_id', $purchaseInfo['product_relation_id'])->exists()) {
                    if ($purchaseType == 1) {
                        $productRelation = ProductRelation::where('product_relation_id', $purchaseInfo['product_relation_id'])
                            ->increment('stock', $purchaseInfo['purchase_number']);
                    } else if ($purchaseType == 2) {
                        $productRelation = ProductRelation::where('product_relation_id', $purchaseInfo['product_relation_id'])
                            ->decrement('stock', $purchaseInfo['purchase_number']);
                    }
                }
            }

            if ($purchaseType == 1) {
                $product = Product::where('product_id', $purchaseInfo['product_id'])
                    ->increment('product_stock', $purchaseInfo['purchase_number']);
                if(!empty($specsValue)){
                    $message = 'ID為' . $purchaseInfo['product_id'] . '的產品進倉，規格為' . $specsValue . '的庫存增加： ' . $purchaseInfo['purchase_number'];
                }else{
                    $message = 'ID為' . $purchaseInfo['product_id'] . '的產品進倉庫存增加： ' . $purchaseInfo['purchase_number'];
                }


                $costInsert = ActivityLog::insert([
                    'admin_name' => $admin['username'],
                    'admin_id' => $admin['admin_id'],
                    'activity_type' => 3,
                    'activity_value' => $message,
                    'created_at' => self::getDate()
                ]);
            } else if ($purchaseType == 2) {
                $product = Product::where('product_id', $purchaseInfo['product_id'])
                    ->decrement('product_stock', $purchaseInfo['purchase_number']);
                if(!empty($specsValue)){
                    $message = 'ID為' . $purchaseInfo['product_id'] . '的產品退倉，規格為' . $specsValue . '的庫存減少： ' . $purchaseInfo['purchase_number'];
                }else{
                    $message = 'ID為' . $purchaseInfo['product_id'] . '的產品庫存減少： ' . $purchaseInfo['purchase_number'];
                }

                $costInsert = ActivityLog::insert([
                    'admin_name' => $admin['username'],
                    'admin_id' => $admin['admin_id'],
                    'activity_type' => 3,
                    'activity_value' => $message,
                    'created_at' => self::getDate()
                ]);
            }

            $productRecordUpdate = $this->where('purchase_id', $purchaseInfo['purchase_id'])
                ->update(['purchase_state' => $purchaseType]);

            if ($productRelation && $product && $productRecordUpdate && $costInsert) {
                DB::commit();
                return $this->success(__('web.set_success'));
            } else {
                DB::rollBack();
                return $this->error(__('web.set_fail'));
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error(__('web.set_fail') . ': ' . $e->getMessage());
        }
    }

}
