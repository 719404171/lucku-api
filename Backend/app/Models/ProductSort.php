<?php
/*
 * File: ProductSort.php
 * Project: NewDemo
 * Created Time: 2024-07-01 02:31:25 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-07-15 3:29:03 pm
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
 * App\Models\ProductSort
 */
class ProductSort extends Base
{
    use Notifiable;
    protected $table = 'product_sort';
    protected $primaryKey = 'product_sort_id';
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
        $this->AppDetail = ['product_sort_id',$this->language.'product_sort_name as product_sort_name','product_sort_img','status','created_at'];
        //每个值加前缀
       $table=$this->table;
       $this->PrefixAppDetail = array_map(function($item) use ($table) { return $table.'.' . $item ;}, $this->AppDetail);

    }

/*********************************************************关联**********************************************************************/
    /**
     * 關聯產品表
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function product(){
        return $this->hasMany('App\Models\Product','product_sort_id','product_sort_id');
    }




/*********************************************************admin**********************************************************************/

    /**
     * 獲取分類列表
     * @param $data
     * @param $language
     * @return mixed
     */
    public function getProductSortListWeb($data,$language){
        $limit = $data['limit'];
        $page = ($data['page'] - 1) * $limit;
        $where[] = array('status', '<>', -1);
        if(isset($data['status']) && (!empty($data['status']) || is_numeric($data['status']))){
            $where['status'] = $data['status'];
        }
        if (!empty($data['product_sort_name'])) {
            $where[] = array($language.'product_sort_name', 'like', '%' . $data['product_sort_name'] . '%');
        }
        $datas['data'] = $this->where($where)
            ->offset($page)->limit($limit)
            ->orderBy('product_sort_id','desc')
            ->get()->toArray();
        $datas['count'] = $this->where($where)->count();
        return $datas;
    }

    /**
     * 添加/編輯產品分類
     * @param $data
     * @return array
     */
    public function handleProductSortListWeb($data){
        $where[] = array('status', '<>', -1);
        if(empty($data['product_sort_id'])){
            $data['created_at'] = self::getDate();
        }else{
            $where[] = array('product_sort_id', '<>', $data['product_sort_id']);
        }
        $hasAttributeName = $this->where(function ($query) use($data,$where){
            $query->where('product_sort_name',$data['product_sort_name'])->where($where);
        })->orWhere(function ($q)use($data,$where){
            $q->where('en_product_sort_name',$data['en_product_sort_name'])->where($where);
        })->first();
        if(!empty($hasAttributeName)){
            return $this->error(__('web.name_duplication'));
        }else{
            if(($data['status'] != 1) && !empty($data['product_sort_id'])){
                $hasProduct = Product::where('product_sort_id',$data['product_sort_id'])->first();
                if(!empty($hasProduct)){
                    return $this->error(__('web.existingProduct'));
                }
            }
            $res = $this->updateOrInsert(
                !empty($data['product_sort_id'])
                    ? ['product_sort_id' => $data['product_sort_id']]
                    : ['product_sort_name' => $data['product_sort_name']],
                $data
            );
            if($res){
                return $this->success(__('web.success'));
            }else{
                return $this->error(__('web.network_error'));
            }
        }
    }

    /**
     * 刪除產品分類
     * @param $productSortID
     * @return array
     */
    public function deleteProductSortListWeb($productSortID){
        $hasProduct = Product::where('product_sort_id',$productSortID)->where('product_status', 1)->first();
        if(!empty($hasProduct)){
            return $this->error(__('web.existingProduct'));
        }else{
            $deleteProductSort = $this->where('product_sort_id',$productSortID)->update(['status'=>-1]);
            if($deleteProductSort){
                return $this->success(__('web.delete_success'));
            }else{
                return $this->error(__('web.delete_fail'));
            }
        }
    }

    /**
     * 獲取所有分類列表
     * @param $language
     * @return array
     */
    public function getAllProductSortWeb($language){
        $data = $this->where('status',1)->select('product_sort_id',$language.'product_sort_name as product_sort_name')->get()->toArray();
        return $this->success(__('web.success'),$data);
    }

    /**
     * 獲取所有分類商品
     * @param $data
     * @param $language
     * @return array
     */
    public function getAllSortProductWeb($data,$language){
        $limit = $data['limit'];
        $page = ($data['page'] - 1) * $limit;
        $where[] = array('product_status', '=', 1);
        if (!empty($data['product_name'])) {
            $where[] = array($language.'product_name', 'like', '%' . $data['product_name'] . '%');
        }
        if (!empty($data['product_type'])) {
            $where['product_type'] =  $data['product_type'];
        }
        if (!empty($data['product_sort_id'])) {
            $where['product_sort_id'] =  $data['product_sort_id'];
        }

        $datas['data'] = Product::with(['productRelation','productSpecsKey'=>function ($query){
            $query->with('productSpecsValue');
        }])->where($where)
            ->offset($page)->limit($limit)
            ->select('product_id','product_sort_id',$language.'product_name as product_name','product_img','is_specs','product_type','product_price',
                'product_cost','product_stock')
            ->get()->toArray();

        $datas['count'] = Product::where($where)
            ->offset($page)->limit($limit)
            ->select('product_id','product_sort_id',$language.'product_name as product_name','product_img','is_specs','product_type','product_price',
                'product_cost','product_stock')
            ->count();

//        $datas = $this->with(['product'=>function($query) use($where,$page,$limit,$language){
//            $query->with(['productRelation','productSpecsKey'=>function($q){
//                $q->with('productSpecsValue');
//            }])->where($where)
//                ->offset($page)->limit($limit)
//                ->select('product_id','product_sort_id',$language.'product_name as product_name','product_img','is_specs','product_type','product_price',
//                'product_cost','product_stock');
//        }]) ->where('status',1)
//            ->select('product_sort_id',$language.'product_sort_name as product_sort_name')
//            ->withCount(['product'=>function($query) use($where){
//                $query->where($where);
//            }])
//            ->get()->toArray();
        if(!empty($datas['data'])){
            $shop = ShopSetting::where('shop_id',1)->first();
            for($i=0;$i<count($datas['data']);$i++){
                $datas['data'][$i]['is_tostore'] = $shop['is_tostore'];
                $datas['data'][$i]['tostore_price'] = $shop['tostore_price'];
                if(!empty($datas['data'][$i]['product_relation'])){
                    for($k=0;$k<count($datas['data'][$i]['product_relation']);$k++){
                        $datas['data'][$i]['product_relation'][$k]['product_specs_value'] = [];
                        $datas['data'][$i]['product_relation'][$k]['product_specs_value'] = ProductSpecsValue::whereIn('product_specs_value_id',explode('-',$datas['data'][$i]['product_relation'][$k]['product_specs_value_id']))->get()->toArray();
                    }
                }
            }
        }

        return $this->success(__('web.success'),$datas);
    }



/**********************************************************api**********************************************************************/


    /**
     * 根據服務或者產品獲得產品分类列表
     * @param $data
     * @return mixed
     */
    public function getSortByType($data){
        $limit = empty($data['limit']) ? 10 : $data['limit'];
        $page =  ( $data['page'] - 1 ) * $limit;
        $page = empty($data['product_type']) ? 1 : $data['page'];
        if(!empty($data['product_type'])){
          $where[] = array('product.product_type', '=', $data['product_type']);
        }
        $where[] = array('product.product_status', '<>', -1);
        $effects=array();
        $info = $this->join('product','product.product_sort_id','=','product_sort.product_sort_id')->where($where)->select($this->PrefixAppDetail)
                     ->orderBy('product.product_sort_id','desc')->distinct('product.product_sort_id')->paginate($limit , $page);
        $effects=$info->items();
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








    // /**
    //  * 獲取分類列表
    //  * @param $data
    //  * @return mixed
    //  */
    // public function getProductSortListApi($data){
    //     $limit = empty($data['limit']) ? 10 : $data['limit'];
    //     $page = empty($data['page']) ? 1 : $data['page'];
    //     $where[] = array('status', '<>', -1);
    //     $effects=array();
    //     $info = $this->where($where)->select($this->AppDetail)->orderBy('product_sort_id','desc')->paginate($limit , $page);
    //     $effects=$info->items();
    //     $results['count']=$info->count();
    //     $results['currentPage']=$info->currentPage();
    //     $results['limit']=$info->perPage();
    //     $results['lastPage']=$info->lastPage();
    //     $results['data']=$effects;
    //     if(!empty($effects)){
    //         return $this->success( __('api.list_success'), $results);
    //     }else{
    //         return $this->success( __('api.list_null'), $results);
    //     }
    // }



































}
