<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/6/24
 * Time: 10:34
 */

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

class SpecsKey extends Base
{
    use Notifiable;
    protected $table = 'specs_key';
    protected $primaryKey = 'specs_key_id';
    public $timestamps = false;

//    public function specsRelation(){
//        return $this->hasMany('App\Models\SpecsRelation','specs_key_id','specs_key_id');
//    }
//    /**
//     * 關聯規格值
//     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
//     */
//    public function specsValue(){
//        return $this->belongsToMany('App\Models\SpecsValue','specs_relation','specs_key_id','specs_value_id');
//    }

    public function specsValue(){
        return $this->hasMany('App\Models\SpecsValue','specs_key_id','specs_key_id');
    }

//    /**
//     * 關聯規格分類
//     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
//     */
//    public function specsSort(){
//        return $this->belongsToMany('App\Models\SpecsSort','specs_sort_relation','specs_key_id','specs_sort_id');
//    }

//    /**
//     * 获取規格列表
//     * @param $data
//     * @param $language
//     * @return mixed
//     */
//    public function getSpecsListWeb($data,$language){
//        $limit = $data['limit'];
//        $page = ($data['page'] - 1) * $limit;
//        $where[] = array('specs_key.status','=',1);
//        $specs['data'] = $this->with(['specsValue'=>function($query) use($language){
//            $query->where('specs_value.status',1)->select('specs_value.specs_value_id',$language.'specs_value_name as specs_value_name');
//        },'specsSort'=>function($q)use($language){
//            $q->select('specs_sort.specs_sort_id',$language.'specs_sort_name as specs_sort_name');
//        }])
//            ->where(function ($query) use ($data,$language){
//                if(!empty($data['specs_key_name'])){
//                    $query->where($language.'specs_key_name','like','%' . $data['specs_key_name'] . '%');
//                }
//            })
//            ->where($where)
//            ->select('specs_key_id',$language.'specs_key_name as specs_key_name','status')
//            ->offset($page)->limit($limit)
//            ->get()->toArray();
//        $specs['count'] = $this->with(['specsRelation'])
//            ->where(function ($query) use ($data,$language){
//                if(!empty($data['specs_key_name'])){
//                    $query->where($language.'specs_key_name','like','%' . $data['specs_key_name'] . '%');
//                }
//            })
//            ->where($where)
//            ->count();
//        return $specs;
//    }
//
//    /**
//     * 添加規格名
//     * @param $specsValue
//     * @param $specsSort
//     * @param $specsKey
//     * @return array
//     */
//    public function addSpecsKeyWeb($specsValue,$specsSort,$specsKey){
//        DB::beginTransaction();
//        $hasName = $this->where('specs_key_name',$specsKey['specs_key_name'])->orWhere('en_specs_key_name',$specsKey['en_specs_key_name'])->first();
//        if(empty($hasName)){
//            $specsKeyId = $this->insertGetId(['specs_key_name'=>$specsKey['specs_key_name'],'en_specs_key_name'=> $specsKey['en_specs_key_name']]);
//            if(!empty($specsKeyId)){
//                for($i=0;$i<count($specsValue);$i++){
//                    $valueData[$i]['specs_value_id'] = $specsValue[$i];
//                    $valueData[$i]['specs_key_id'] = $specsKeyId;
//                }
//                $specsRelationAdd = SpecsRelation::insert($valueData);
//                for($j=0;$j<count($specsSort);$j++){
//                    $sortData[$j]['specs_sort_id'] = $specsSort[$j];
//                    $sortData[$j]['specs_key_id'] = $specsKeyId;
//                }
//                $specsSortAdd = SpecsSortRelation::insert($sortData);
//                if($specsRelationAdd && $specsSortAdd){
//                    DB::commit();
//                    return $this->success(__('web.add_success'));
//                }else{
//                    DB::rollBack();
//                    return $this->error(__('web.add_fail'));
//                }
//            }else{
//                DB::rollBack();
//                return $this->error(__('web.add_fail'));
//            }
//        }else{
//            return $this->error(__('web.name_duplication'));
//        }
//    }

}
