<?php
/*
 * File: WastageRecord.php
 * Project: NewDemo
 * Created Time: 2024-07-01 02:31:25 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-07-03 5:01:33 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2024 成都睿創科技有限公司
 */



namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

class WastageRecord extends Base
{
    use Notifiable;
    protected $table = 'wastage_record';
    protected $primaryKey = 'wastage_id';
    public $timestamps = true;

    /**
     * 獲取損耗記錄
     * @param $data
     * @param $language
     * @return mixed
     */
    public function getWastageRecordListWeb($data,$language){
        $limit = empty($data['limit']) ? 10 : $data['limit'];
        $page =  ( $data['page'] - 1 ) * $limit;
        $time = [];
        $where[] = array('wastage_record.wastage_status', '<>', -1);
        if (!empty($data['product_name'])) {
            $where[] = array('product.'.$language.'product_name', 'like', '%' . $data['product_name'] . '%');
        }
        if(!empty($data['time'])){
            $time['start_time'] = $data['time'][0];
            $time['end_time'] = $data['time'][1];
        }
        $datas['data'] = $this->join('product','product.product_id','=','wastage_record.product_id')
            ->join('admins','admins.admin_id','=','wastage_record.admin_id')
            ->where($where)
            ->where(function ($query) use ($time){
                if(!empty($time) && $time['start_time'] != ''){
                    return $query->whereBetween('wastage_record.created_at',$time);
                }
            })
            ->select('wastage_record.*','product.'.$language.'product_name as product_name','admins.username')
            ->offset($page)->limit($limit)
            ->orderBy('wastage_record.created_at', 'desc')
            ->get()->toArray();
        $datas['count'] = $this->join('product','product.product_id','=','wastage_record.product_id')
            ->join('admins','admins.admin_id','=','wastage_record.admin_id')
            ->where($where)
            ->where(function ($query) use ($time){
                if(!empty($time) && $time['start_time'] != ''){
                    return $query->whereBetween('wastage_record.created_at',$time);
                }
            })->count();
        return $datas;
    }

    /**
     * 添加損耗記錄
     * @param $wastageRecord
     * @return array
     */
    public function addWastageRecordListWeb($wastageRecord){
        DB::beginTransaction();
        $wastageRecord['created_at'] = self::getDate();
        $productRelation = true;
        if(!empty($wastageRecord['product_relation_id'])){
            $productRelation = ProductRelation::where('product_relation_id',$wastageRecord['product_relation_id'])->decrement('stock',$wastageRecord['wastage_number']);
        }
        $product = Product::where('product_id',$wastageRecord['product_id'])->decrement('product_stock',$wastageRecord['wastage_number']);
        if($product && $productRelation){
            $wastageRecordAdd = $this->insert($wastageRecord);
            if($wastageRecordAdd){
                DB::commit();
                return $this->success(__('web.add_success'));
            }else{
                DB::rollBack();
                return $this->error(__('web.add_fail'));
            }
        }else{
            DB::rollBack();
            return $this->error(__('web.add_fail'));
        }
    }

    /**
     * 编辑損耗記錄
     * @param $wastageRecord
     * @return array
     */
    public function editWastageRecordListWeb($wastageRecord){
        DB::beginTransaction();
        $productRelation = true;
        $product = true;

        $wastageRecordInfo = $this->where('wastage_id',$wastageRecord['wastage_id'])->select('wastage_number','product_relation_id')->first();

        if($wastageRecordInfo['wastage_number'] != $wastageRecord['wastage_number']){
            $wastageNumber = ($wastageRecord['wastage_number'] - $wastageRecordInfo['wastage_number']);
            if($wastageNumber > 0 && $wastageNumber != 0){
                if(!empty($wastageRecordInfo['product_relation_id'])){
                    if(ProductRelation::where('product_relation_id',$wastageRecordInfo['product_relation_id'])->count() > 0){
                        $productRelation = ProductRelation::where('product_relation_id',$wastageRecordInfo['product_relation_id'])->decrement('stock',$wastageNumber);
                    }
                }
                $product = Product::where('product_id',$wastageRecord['product_id'])->decrement('product_stock',$wastageNumber);
            }else if($wastageNumber < 0 && $wastageNumber != 0){
                if(!empty($wastageRecordInfo['product_relation_id'])){
                    if(ProductRelation::where('product_relation_id',$wastageRecordInfo['product_relation_id'])->count() > 0){
                        $productRelation = ProductRelation::where('product_relation_id',$wastageRecordInfo['product_relation_id'])->increment('stock',$wastageNumber);
                    }
                }
                $product = Product::where('product_id',$wastageRecord['product_id'])->increment('product_stock',$wastageNumber);
            }
        }

        $wastageRecordUpdate = $this->where('wastage_id',$wastageRecord['wastage_id'])->update($wastageRecord);
        if($wastageRecordUpdate && $productRelation && $product){
            DB::commit();
            return $this->success(__('web.edit_success'));
        }else{
            DB::rollBack();
            return $this->error(__('web.edit_fail'));
        }
    }

    /**
     * 删除損耗記錄
     * @param $wastageID
     * @return array
     */
    public function deleteWastageRecordListWeb($wastageID){
        $data = self::softDelete('wastage_record','wastage_id',$wastageID,'wastage_status',-1);
        if($data){
            return $this->success(__('web.delete_success'));
        }else{
            return $this->error(__('web.delete_fail'));
        }
    }

}
