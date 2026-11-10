<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/6/26
 * Time: 14:03
 */

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
class ProductSpecsKey extends Base
{
    use Notifiable;
    protected $table = 'product_specs_key';
    protected $primaryKey = 'product_specs_key_id';
    public $timestamps = false;

    /**
     * 關聯產品規格值
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function productSpecsValue(){
        return $this->hasMany('App\Models\ProductSpecsValue', 'product_specs_key_id', 'product_specs_key_id');
    }

    /**
     * 添加產品規格組合
     * @param $specsKey
     * @param $productID
     * @param $language
     * @return array
     */
    public function addProductSpecsKeyWeb($specsKey,$productID,$language){
        DB::beginTransaction();
        $productSpecsKey = $this->where('product_id',$productID)->select('product_specs_key_id')->get()->toArray();
        $productSpecsKeyDelete = true;
        $productSpecsValueDelete = true;
        $productRelationDelete = true;
        if(!empty($productSpecsKey)){
            if(!empty(ProductSpecsValue::whereIn('product_specs_key_id',$productSpecsKey)->get()->toArray())){
                $productSpecsValueDelete = ProductSpecsValue::whereIn('product_specs_key_id',$productSpecsKey)->delete();
            }
            $productSpecsKeyDelete = $this->whereIn('product_specs_key_id',$productSpecsKey)->delete();
        }
        if((ProductRelation::where('product_id',$productID)->count()) > 0){
            $productRelationDelete = ProductRelation::where('product_id',$productID)->delete();
        }
        if($productSpecsKeyDelete && $productSpecsValueDelete && $productRelationDelete){
            $addProductSpecsKey = true;
            $addProductSpecsValue = true;
            $productSpecsKeyID = [];
            for($i=0;$i<count($specsKey);$i++){
                $ProductSpecsKey[$i]['product_specs_key'] = $specsKey[$i]['product_specs_key'];
                $ProductSpecsKey[$i]['en_product_specs_key'] = $specsKey[$i]['en_product_specs_key'];
                $ProductSpecsKey[$i]['product_id'] = $productID;
                $productSpecsKeyID[$i] = $this->insertGetId($ProductSpecsKey[$i]);
                if(empty($productSpecsKeyID[$i])){
                    $addProductSpecsKey = false;
                    break;
                }
                for($j=0;$j<count($specsKey[$i]['specs_value']);$j++){
                    $productSpecsValueName[$j]['product_specs_value'] = $specsKey[$i]['specs_value'][$j]['product_specs_value'];
                    $productSpecsValueName[$j]['en_product_specs_value'] = $specsKey[$i]['specs_value'][$j]['en_product_specs_value'];
                    $productSpecsValueName[$j]['product_specs_key_id'] = $productSpecsKeyID[$i];
                    $productSpecsValueID = ProductSpecsValue::insert($productSpecsValueName[$j]);
                    if(!$productSpecsValueID){
                        $addProductSpecsValue = false;
                        break;
                    }
                }
            }
            if($addProductSpecsKey && $addProductSpecsValue){
                $productSpecsKeyInfo = $this->with(['productSpecsValue'=>function($query) use($language){
                    $query->select('product_specs_value_id','product_specs_key_id',$language.'product_specs_value as product_specs_value');
                }])
                    ->where('product_id',$productID)
                    ->select('product_specs_key_id',$language.'product_specs_key as product_specs_key')
                    ->get()->toArray();
                DB::commit();
                return $this->success(__('web.success'),$productSpecsKeyInfo);
            }else{
                DB::rollBack();
                return $this->error(__('web.network_error'));
            }
        }else{
            DB::rollBack();
            return $this->error(__('web.network_error'));
        }
    }

}
