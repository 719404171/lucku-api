<?php
/*
 * File: Product.php
 * Project: NewDemo
 * Created Time: 2024-07-01 02:31:25 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2026-06-17 4:01:19 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2024 成都睿創科技有限公司
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
 * App\Models\Product
 */
class Product extends Base
{
    use Notifiable;
    protected $table = 'product';
    protected $primaryKey = 'product_id';
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
        $this->AppDetail = ['product_id','product_sort_id','product_name','product_img','is_priority','is_album',
                            'is_integral','integral_num','product_type','product_price','product_cost',
                            'product_stock','product_describe','product_synopsis','browse_number','product_status','product_pay_type','deal_condition','created_at','updated_at'];
        //每个值加前缀
       $table=$this->table;
       $this->PrefixAppDetail = array_map(function($item) use ($table) { return $table.'.' . $item ;}, $this->AppDetail);

    }
/*********************************************************全局作用域注册**********************************************************************/

    // protected static function boot(){
    //    parent::boot();
	//    static::addGlobalScope('product', function (Builder $builder) {
	// 	  return $builder->where('product.product_status', '<>', -1);
	//    });
    // }

/*********************************************************关联**********************************************************************/
    /**
     * 關聯產品相冊表
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function productAlbum(){
        return $this->hasMany('App\Models\ProductAlbum', 'product_id', 'product_id');
    }

    /**
     * 關聯產品規格組合表
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function productRelation(){
        return $this->hasMany('App\Models\ProductRelation', 'product_id', 'product_id');
    }

    /**
     * 關聯產品規格表
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function productSpecsKey(){
        return $this->hasMany('App\Models\ProductSpecsKey', 'product_id', 'product_id');
    }

    /**
     * 關聯產品標籤表
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function productTag(){
        return $this->hasMany('App\Models\ProductTag', 'product_id', 'product_id');
    }

    public function userCollect(){
        return $this->hasMany('App\Models\UserCollect', 'product_id', 'product_id');
    }

/**********************************************************admin**********************************************************************/
    /**
     * 獲取產品列表
     * @param $data
     * @param $language
     * @return mixed
     */
    public function getProductListWeb($data,$language){
        $limit = $data['limit'];
        $page = ($data['page'] - 1) * $limit;
        $where[] = array('product.product_status', '<>', -1);
        if (!empty($data['product_name'])) {
            $where[] = array('product.'.$language.'product_name', 'like', '%' . $data['product_name'] . '%');
        }
		if (!empty($data['product_id'])) {
            $where['product.product_id'] =  $data['product_id'];
        }
        if (!empty($data['product_barcode'])) {
            $where[] = array('product.product_barcode', 'like', '%' . $data['product_barcode'] . '%');
        }
        if (!empty($data['product_sort_id'])) {
            $where['product.product_sort_id'] =  $data['product_sort_id'];
        }
        $datas['data'] = $this->with(['productAlbum','productTag','productRelation','productSpecsKey'=>function($q){
            $q->with('productSpecsValue');
        }])->join('product_sort','product.product_sort_id','=','product_sort.product_sort_id')
            ->where($where)
            ->select('product.*',DB::raw('0 as is_min_stock'),'product_sort.'.$language.'product_sort_name as product_sort_name')
            ->offset($page)->limit($limit)
            ->orderBy('product.created_at', 'desc')
            ->get()->toArray();
        $datas['count'] = $this->where($where)->count();
        $shopInfo= ShopSetting::where('shop_id',1)->select('minStock','is_tips')->first();
        for($i=0;$i<count($datas['data']);$i++){
            if(!empty($datas['data'][$i]['product_relation'])){
                for($j=0;$j<count($datas['data'][$i]['product_relation']);$j++){
                    $datas['data'][$i]['product_relation'][$j]['product_specs_value'] = [];
                    $datas['data'][$i]['product_relation'][$j]['product_specs_value'] = ProductSpecsValue::whereIn('product_specs_value_id',explode('-',$datas['data'][$i]['product_relation'][$j]['product_specs_value_id']))->get()->toArray();
                }
            }
            if($shopInfo['is_tips'] == 1){
                if(($datas['data'][$i]['product_stock'] <= $shopInfo['minStock']) && ($datas['data'][$i]['product_type'] == 1)){
                    $datas['data'][$i]['is_min_stock'] = 1;
                }
            }
        }
        return $datas;
    }

    /**
     * 添加產品
     * @param $album
     * @param $tag
     * @param $product
     * @return array
     */
    public function addProductWeb($album,$tag,$product,$token){
        DB::beginTransaction();
        $product['created_at'] = self::getDate();
        $productID = $this->insertGetId($product);
        $admin = Admins::where('token',$token)->select('admin_id','username')->first();
        if(!empty($productID)){
            $albumInsert = true;
            $tagInsert = true;
            $albumInfo = [];
            $tagInfo = [];
            if(!empty($album)){
                for($i=0;$i<count($album);$i++){
                    $albumInfo[$i] = [
                        'product_id' => $productID,
                        'image_url' => $album[$i]
                    ];
                }
                $albumInsert = ProductAlbum::insert($albumInfo);
            }
            if(!empty($tag)){
                for($j=0;$j<count($tag);$j++){
                    $tagInfo[$j] = [
                        'product_id' => $productID,
                        'product_tag_name' => $tag[$j]['product_tag_name'],
                        'en_product_tag_name' => $tag[$j]['en_product_tag_name']
                    ];
                }
                $tagInsert = ProductTag::insert($tagInfo);
            }
            if($albumInsert && $tagInsert){
                $cn_content = '增加了名稱為：'.$product['product_name'].' 的產品！';
                $en_content = 'Added a product named:：'.$product['product_name'];
                $configInfo = [//增加产品记录操作日誌
                    'admin_name' => $admin['username'],
                    'admin_id' => $admin['admin_id'],
                    'activity_type' => 11,//11新增產品
                    'activity_value' => $cn_content,
                    'en_activity_value' => $en_content,
                    'created_at' =>  self::getDate()
                ];
                $configInsert = ActivityLog::insert($configInfo);
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
     * 編輯產品
     * @param $album
     * @param $tag
     * @param $product
     * @param $token
     * @return array
     */
    public function editProductWeb($album,$tag,$product,$token){
        DB::beginTransaction();
        $admin = Admins::where('token',$token)->select('admin_id','username')->first();
        $costeditInfo = $this->where('product_id',$product['product_id'])->select('product_cost')->first()->product_cost;
        $productUpdate = $this->where('product_id',$product['product_id'])->update($product);
        if($productUpdate){
            $albumDelete = true;
            $tagDelete = true;
            if(ProductAlbum::where('product_id',$product['product_id'])->count() > 0) $albumDelete = ProductAlbum::where('product_id',$product['product_id'])->delete();
            if(ProductTag::where('product_id',$product['product_id'])->count() > 0) $tagDelete = ProductTag::where('product_id',$product['product_id'])->delete();
            if($albumDelete && $tagDelete){
                $albumInsert = true;
                $tagInsert = true;
                $albumInfo = [];
                $tagInfo = [];
                if(!empty($album)){
                    for($i=0;$i<count($album);$i++){
                        $albumInfo[$i] = [
                            'product_id' => $product['product_id'],
                            'image_url' => $album[$i]
                        ];
                    }
                    $albumInsert = ProductAlbum::insert($albumInfo);
                }
                if(!empty($tag)){
                    for($j=0;$j<count($tag);$j++){
                        $tagInfo[$j] = [
                            'product_id' => $product['product_id'],
                            'product_tag_name' => $tag[$j]['product_tag_name'],
                            'en_product_tag_name' => $tag[$j]['en_product_tag_name']
                        ];
                    }
                    $tagInsert = ProductTag::insert($tagInfo);
                }
                if($albumInsert && $tagInsert){
                    $costInsert = false;
                    if($costeditInfo != $product['product_cost']){
                        $message = 'ID為'.$product['product_id'].'的產品，更改成本為： '.$product['product_cost'];
                        $en_message = 'For the product with ID'.$product['product_id'].',  the change cost is:'.$product['product_cost'];
                        $costInsertInfo = [
                            'admin_name' => $admin['username'],
                            'admin_id' => $admin['admin_id'],
                            'activity_type' => 5,
                            'activity_value' => $message,
                            'en_activity_value' => $en_message,
                            'created_at' =>  self::getDate()
                        ];
                        $costInsert = ActivityLog::insert($costInsertInfo);
                    }
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
        }else{
            DB::rollBack();
            return $this->error(__('web.edit_fail'));
        }
    }

    /**
     * 刪除產品
     * @param $productID
     * @return array
     */
    public function deleteProductWeb($productID){
        $data = self::softDelete('product','product_id',$productID,'product_status',-1);
        if($data){
            return $this->success(__('web.delete_success'));
        }else{
            return $this->error(__('web.delete_fail'));
        }
    }

    /**
     * 獲取產品規格
     * @param $productID
     * @return array
     */
    public function getProductSpecsWeb($productID){
        $datas= $this->with(['productRelation','productSpecsKey'=>function($q){
            $q->with('productSpecsValue');
        }])->where('product_id',$productID)
            ->select('product_id','is_specs','product_price','product_cost','product_stock','product_type')
            ->first()->toArray();
        if(!empty($datas['product_relation'])){
            for($j=0;$j<count($datas['product_relation']);$j++){
                $datas['product_relation'][$j]['product_specs_value'] = [];
                $datas['product_relation'][$j]['product_specs_value'] = ProductSpecsValue::whereIn('product_specs_value_id',explode('-',$datas['product_relation'][$j]['product_specs_value_id']))->get()->toArray();
            }
        }
        return $this->success(__('web.success'),$datas);
    }

    /**
     * 編輯產品規格
     * @param $productRelation
     * @param $product
     * @param $token
     * @return array
     */
    public function editProductSpecsWeb($productRelation,$product,$token){
        DB::beginTransaction();
        $admin = Admins::where('token',$token)->select('admin_id','username')->first();
        $productInfo = $this->where('product_id',$product['product_id'])->select('product_stock','product_type')->first();
        $costeditInfo = $this->where('product_id',$product['product_id'])->select('product_cost')->first()->product_cost;
        $productStockNum = 0;
        $costInsert = false;
        $productStock = 0;
        if($product['is_specs'] == 0){
            $productUpdate = $this->where('product_id',$product['product_id'])->update($product);
            if($productUpdate){
                // 修改成本日誌記錄部分
                if($costeditInfo != $product['product_cost']){
                    $costmessage = 'ID為'.$product['product_id'].'的產品，更改成本為：'.$product['product_cost'];
                    $en_costmessage = 'For the product with ID'.$product['product_id'].', the change cost is:'.$product['product_cost'];
                    $costInsertInfo = [
                        'admin_name' => $admin['username'],
                        'admin_id' => $admin['admin_id'],
                        'activity_type' => 5,
                        'activity_mode' => 1,
                        'activity_number' => $product['product_cost'],
                        'activity_value' => $costmessage,
                        'en_activity_value' => $en_costmessage,
                        'created_at' => self::getDate()
                    ];
                    $costInsert = ActivityLog::insert($costInsertInfo);
                }
                if($productInfo['product_type'] == 1){
                    if($productInfo['product_stock'] != $product['product_stock']){
                        $productStockNum = ($product['product_stock'] - $productInfo['product_stock']);
                    }else{
                        DB::commit();
                        return $this->success(__('web.success'));
                    }
                }else{
                    DB::commit();
                    return $this->success(__('web.success'));
                }
            }else{
                DB::rollBack();
                return $this->error(__('web.edit_fail'));
            }
        }else{
            $insertProductRelation = true;
            for($i=0;$i<count($productRelation);$i++){
                $productRelationInfo[$i] = [
                    'product_relation_id' => $productRelation[$i]['product_relation_id'],
                    'product_id' => $product['product_id'],
                    'product_specs_value_id' => $productRelation[$i]['product_specs_value_id'],
                    'price' => $productRelation[$i]['price'],
                    'cost' => $productRelation[$i]['cost'],
                    'stock' => $productRelation[$i]['stock']
                ];
                if($productInfo['product_type'] == 1){
                    $productStock += $productRelation[$i]['stock'];
                }
                $productRelationInsert = ProductRelation::updateOrInsert(['product_relation_id'=> $productRelation[$i]['product_relation_id']],$productRelationInfo[$i]);
                if(!$productRelationInsert){
                    $insertProductRelation = false;
                    break;
                }
            }
            if($insertProductRelation){
                $productStockUpdate = $this->where('product_id',$product['product_id'])->update(['product_stock'=>$productStock,'is_specs'=>$product['is_specs']]);
                if($productStockUpdate){
                    if($productInfo['product_type'] == 1){
                        if($productInfo['product_stock'] != $productStock){
                            $productStockNum = ($productStock - $productInfo['product_stock']);
                        }else{
                            DB::commit();
                            return $this->success(__('web.success'));
                        }
                    }else{
                        DB::commit();
                        return $this->success(__('web.success'));
                    }
                }else{
                    DB::rollBack();
                    return $this->error(__('web.edit_fail'));
                }
            }else{
                DB::rollBack();
                return $this->error(__('web.edit_fail'));
            }
        }
        if($productStockNum > 0){
            $message = 'ID為'.$product['product_id'].'的產品 +'.$productStockNum.'庫存';
            $en_message =  'Product with ID'.$product['product_id'].'+'.$productStockNum.'inventory';
            $activity_mode = 1;
            $productStockInfo = [
                'admin_name' => $admin['username'],
                'admin_id' => $admin['admin_id'],
                'activity_type' => 3,
                'activity_mode' => $activity_mode,
                'activity_number' => $productStockNum,
                'activity_value' => $message,
                'en_activity_value' => $en_message,
                'created_at' =>  self::getDate()
            ];
            $productStockInsert = ActivityLog::insert($productStockInfo);
        }else if($productStockNum < 0){
            $message = 'ID為'.$product['product_id'].'的產品 '.$productStockNum.'庫存';
            $en_message =  'Product with ID'.$product['product_id'].'-'.$productStockNum.'inventory';
            $activity_mode = 2;
            $productStockInfo = [
                'admin_name' => $admin['username'],
                'admin_id' => $admin['admin_id'],
                'activity_type' => 3,
                'activity_mode' => $activity_mode,
                'activity_number' => $productStockNum,
                'activity_value' => $message,
                'en_activity_value' => $en_message,
                'created_at' =>  self::getDate()
            ];
            $productStockInsert = ActivityLog::insert($productStockInfo);
        }
        $hasCostOperation = ($costInsert !== false);
        if($productStockInsert||$hasCostOperation){
            DB::commit();
            return $this->success(__('web.success'));
        }else{
            DB::rollBack();
            return $this->error(__('web.edit_fail'));
        }
    }

    /**
     * 獲取所有產品及規格
     * @param $language
     * @return array
     */
    public function getAllProductWeb($language){
        $data = $this->with(['productRelation'=>function($query){
            $query->select('product_relation_id','product_id','product_specs_value_id','stock');
        }])
            ->where('product_status',1)
            ->where('product_type',1)
            ->select('product_id',$language.'product_name as product_name','is_specs','purchase_supplier','purchase_contact')
            ->get()->toArray();
        for($i=0;$i<count($data);$i++){
            if($data[$i]['is_specs'] == 0){
                $data[$i]['product_relation'] = [];
            }else{
                if(!empty($data[$i]['product_relation'])){
                    for($j=0;$j<count($data[$i]['product_relation']);$j++){
                        $data[$i]['product_relation'][$j]['product_specs_value'] = [];
                        $data[$i]['product_relation'][$j]['product_specs_value'] = ProductSpecsValue::whereIn('product_specs_value_id',explode('-',$data[$i]['product_relation'][$j]['product_specs_value_id']))->get()->toArray();
                    }
                }
            }
        }
        return $data;
    }


/**********************************************************api**********************************************************************//*

    /**
     * @param $data
     * @return array
     * 獲取產品列表
     */
    public function getProductListApi($data){
        $limit = empty($data['limit']) ? 10 : $data['limit'];
        $page =  ( $data['page'] - 1 ) * $limit;
        $where[] = array('product.product_status','=',1);
        if (!empty($data['product_name'])) {
            $where[] = array('product.'.$this->language .'product_name', 'like', '%' . $data['product_name'] . '%');
        }
        if (!empty($data['product_sort_id'])) {
            $where[] = array('product.product_sort_id', '=',  $data['product_sort_id'] );
        }
        $effects=array();
        // 排序處理
        $orderBy = 'product.created_at';
        $orderDir = 'desc';
        if (!empty($data['sort_by'])) {
            switch ($data['sort_by']) {
                case 'price_asc':
                    $orderBy = 'product.product_price';
                    $orderDir = 'asc';
                    break;
                case 'price_desc':
                    $orderBy = 'product.product_price';
                    $orderDir = 'desc';
                    break;
            }
        }
        $info = $this->with(['productAlbum','productTag','productRelation','productSpecsKey'=>function($q){
            $q->with('productSpecsValue');
        }])->join('product_sort','product.product_sort_id','=','product_sort.product_sort_id')
            ->select($this->PrefixAppDetail,'product_sort.'.$this->language .'product_sort_name as product_sort_name')
            ->where($where)
            ->orderBy($orderBy, $orderDir)
            ->paginate($limit , $page);
        $effects=$info->items();
        foreach ($effects as $key => $value) {
             //是否收藏
            if (!empty($data['user_id'])) {
                $collect = $this->from('users as u')->join('user_collect as c', 'c.user_id', '=', 'u.user_id')
                    ->where('u.user_status', '=', 1)->where('u.user_id', '=', $data['user_id'])->where('c.product_id', '=', $effects[$key]['product_id'])
                    ->first();
                if (empty($collect)) {
                    $effects[$key]['user_collect'] = 0;
                } else {
                    $effects[$key]['user_collect'] = 1;
                }
            } else {
                $effects[$key]['user_collect'] = 0;
            }
            if(!empty($effects[$key]['product_relation'])){
                foreach ($effects[$key]['product_relation'] as $k => $v) {
                    $effects[$key]['product_relation'][$k]['product_specs_value'] = [];
                    $effects[$key]['product_relation'][$k]['product_specs_value'] =
                    ProductSpecsValue::whereIn('product_specs_value_id',explode('-',$effects[$key]['product_relation'][$k]['product_specs_value_id']))->get()->toArray();
                }
            }
        }
        $results['count']=$info->count();
        $results['currentPage']=$info->currentPage();
        $results['limit']=$info->perPage();
        $results['lastPage']=$info->lastPage();
        $results['data']=$effects;
        if(!empty($effects)){
            return $this->success( __('api.list_success'), $results);
        }else{
            return $this->success( __('api.list_null'), $results);
        }
    }


    /**
     * @param $data
     * @return array
     * 獲取產品详情
     */
    public function getProductDetails($data){
        //瀏覽量加1
        $this->where('product_id', $data['product_id'])->increment('browse_number');
        $where[] = array('product.product_id','=',$data['product_id']);
        $where[] = array('product.product_status','=',1);
        $effects = $this->with(['productAlbum','productTag','productRelation','productSpecsKey'=>function($q){
            $q->with('productSpecsValue');
        }])->join('product_sort','product.product_sort_id','=','product_sort.product_sort_id')
            ->select($this->PrefixAppDetail,'product_sort.'.$this->language .'product_sort_name as product_sort_name')
            ->where($where)->first()->toArray();
        //是否收藏
        if (!empty($data['user_id'])) {
            $collect = $this->from('users as u')->join('user_collect as c', 'c.user_id', '=', 'u.user_id')
                ->where('u.user_status', '=', 1)->where('u.user_id', '=', $data['user_id'])->where('c.product_id', '=', $effects['product_id'])
                ->first();
            if (empty($collect)) {
                $effects['user_collect'] = 0;
            } else {
                $effects['user_collect'] = 1;
            }
            } else {
                $effects['user_collect'] = 0;
            }
        if(!empty($effects['product_relation'])){
            foreach ($effects['product_relation'] as $k => $v) {
                $effects['product_relation'][$k]['product_specs_value'] = [];
                $effects['product_relation'][$k]['product_specs_value'] = ProductSpecsValue::whereIn('product_specs_value_id',explode('-',$effects['product_relation'][$k]['product_specs_value_id']))->get()->toArray();
            }
        }
        $results=$effects;
        if(!empty($effects)){
            return $this->success( __('api.list_success'), $results);
        }else{
            return $this->success( __('api.list_null'), $results);
        }
    }















/**************************************************common(api和admin共用)***********************************************************/

    /**
     * @param $data
     * @return array
     * 獲取產品详情
     */
    public function CommonProductDetails($data){
        $where[] = array('product.product_id','=',$data['product_id']);
        $where[] = array('product.product_status','=',1);
        $effects = $this->with(['productAlbum','productTag','productRelation','productSpecsKey'=>function($q){
            $q->with('productSpecsValue');
        }])->join('product_sort','product.product_sort_id','=','product_sort.product_sort_id')
            ->select($this->PrefixAppDetail,'product_sort.'.$this->language .'product_sort_name as product_sort_name')
            ->where($where)->first()->toArray();
        //是否收藏
        if (!empty($data['user_id'])) {
            $collect = $this->from('users as u')->join('user_collect as c', 'c.user_id', '=', 'u.user_id')
                            ->where('u.user_status', '=', 1)->where('u.user_id', '=', $data['user_id'])->where('c.product_id', '=', $effects['product_id'])
                            ->first();
            if (empty($collect)) {
                $effects['user_collect'] = 0;
            } else {
                $effects['user_collect'] = 1;
            }
            } else {
                $effects['user_collect'] = 0;
            }
        if(!empty($effects['product_relation'])){
            foreach ($effects['product_relation'] as $k => $v) {
                $effects['product_relation'][$k]['product_specs_value'] = [];
                $effects['product_relation'][$k]['product_specs_value'] = ProductSpecsValue::whereIn('product_specs_value_id',explode('-',$effects['product_relation'][$k]['product_specs_value_id']))->get()->toArray();
            }
        }
        return $effects;
    }



    /**
     * @param $data
     * @return array
     * 獲取產品詳情規格
     */
    public function CommonProductSpecs($data){
        $where[] = array('product.product_id','=',$data['product_id']);
        $where[] = array('product.product_status','=',1);
        $effects = $this->with(['productAlbum','productTag','productRelation','productSpecsKey'=>function($q){
            $q->with('productSpecsValue');
        }])->join('product_sort','product.product_sort_id','=','product_sort.product_sort_id')
            ->select($this->PrefixAppDetail,'product_sort.'.$this->language .'product_sort_name as product_sort_name')
            ->where($where)->first()->toArray();
        //是否收藏
        if (!empty($data['user_id'])) {
            $collect = $this->from('users as u')->join('user_collect as c', 'c.user_id', '=', 'u.user_id')
                            ->where('u.user_status', '=', 1)->where('u.user_id', '=', $data['user_id'])->where('c.product_id', '=', $effects['product_id'])
                            ->first();
            if (empty($collect)) {
                $effects['user_collect'] = 0;
            } else {
                $effects['user_collect'] = 1;
            }
            } else {
                $effects['user_collect'] = 0;
            }
        if(!empty($effects['product_relation'])){
            foreach ($effects['product_relation'] as $k => $v) {
                $effects['product_relation'][$k]['product_specs_value'] = [];
                $effects['product_relation'][$k]['product_specs_value'] = ProductSpecsValue::whereIn('product_specs_value_id',explode('-',$effects['product_relation'][$k]['product_specs_value_id']))->get()->toArray();
            }
        }
        return $effects;
    }



    /**
     * @param $data
     * @return array
     * 獲取產品快照
     */
    public function ProductSnapshot($product_id){
        $where[] = array('product.product_id','=',$product_id);
        $where[] = array('product.product_status','=',1);
        $effects = $this->with(['productAlbum','productTag','productRelation','productSpecsKey'=>function($q){
            $q->with('productSpecsValue');
        }])->join('product_sort','product.product_sort_id','=','product_sort.product_sort_id')
            // ->select($this->PrefixAppDetail,'product_sort.'.$this->language .'product_sort_name as product_sort_name')
        ->select('*') ->where($where)->first()->toArray();
        if(!empty($effects['product_relation'])){
            foreach ($effects['product_relation'] as $k => $v) {
                $effects['product_relation'][$k]['product_specs_value'] = [];
                $effects['product_relation'][$k]['product_specs_value'] = ProductSpecsValue::whereIn('product_specs_value_id',explode('-',$effects['product_relation'][$k]['product_specs_value_id']))->get()->toArray();
            }
        }
        //轉json
        return  json_encode($effects);
    }



/**************************************************END***********************************************************/












}
