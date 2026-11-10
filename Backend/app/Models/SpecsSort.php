<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/6/24
 * Time: 10:36
 */

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

class SpecsSort extends Base
{
    use Notifiable;
    protected $table = 'specs_sort';
    protected $primaryKey = 'specs_sort_id';
    public $timestamps = true;

    /**
     * 關聯規格名
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function specsKey(){
        return $this->hasMany('App\Models\SpecsKey','specs_sort_id','specs_sort_id');
    }

    /**
     * 獲取規格分類列表
     * @param $data
     * @param $language
     * @return mixed
     */
    public function getSpecsSortListWeb($data,$language){
        $limit = $data['limit'];
        $page = ($data['page'] - 1) * $limit;
        $specs['data'] = $this->with(['specsKey'=>function($query){
            $query->with(['specsValue']);
        }])
            ->where(function ($query) use ($data,$language){
                if(!empty($data['specs_sort_name'])){
                    $query->where($language.'specs_sort_name','like','%' . $data['specs_sort_name'] . '%');
                }
            })
            ->select('specs_sort_id','specs_sort_name','en_specs_sort_name','status')
            ->offset($page)->limit($limit)
            ->orderBy('specs_sort_id','desc')
            ->get()->toArray();
        $specs['count'] = $this->where(function ($query) use ($data,$language){
            if(!empty($data['specs_sort_name'])){
                $query->where($language.'specs_sort_name','like','%' . $data['specs_sort_name'] . '%');
            }
        })
            ->count();
        return $specs;
    }

    /**
     * 新增規格分類
     * @param $specsKey
     * @param $specsSortName
     * @param $enSpecsSortName
     * @return array
     */
    public function addSpecsSortWeb($specsKey,$specsSortName,$enSpecsSortName){
        DB::beginTransaction();
        $hasName = $this->where(function ($query) use ($specsSortName){
            $query->where('specs_sort_name',$specsSortName)->where('status','<>',-1);
        })->orWhere(function ($q) use ($enSpecsSortName){
            $q->where('en_specs_sort_name',$enSpecsSortName)->where('status','<>',-1);
        })->first();
        if(empty($hasName)){
            $specsSortId = $this->insertGetId(['specs_sort_name'=>$specsSortName,'en_specs_sort_name'=> $enSpecsSortName]);
            if(!empty($specsSortId)){
                $addSpecsKey = true;
                $addSpecsValue = true;
                $specsKeyID = [];
                for($i=0;$i<count($specsKey);$i++){
                    $specsKeyName[$i]['specs_key_name'] = $specsKey[$i]['specs_key_name'];
                    $specsKeyName[$i]['en_specs_key_name'] = $specsKey[$i]['en_specs_key_name'];
                    $specsKeyName[$i]['specs_sort_id'] = $specsSortId;
                    $specsKeyID[$i] = SpecsKey::insertGetId($specsKeyName[$i]);
                    if(empty($specsKeyID[$i])){
                        $addSpecsKey = false;
                        break;
                    }
                    for($j=0;$j<count($specsKey[$i]['specs_value']);$j++){
                        $specsValueName[$j]['specs_value_name'] = $specsKey[$i]['specs_value'][$j]['specs_value_name'];
                        $specsValueName[$j]['en_specs_value_name'] = $specsKey[$i]['specs_value'][$j]['en_specs_value_name'];
                        $specsValueName[$j]['specs_key_id'] = $specsKeyID[$i];
                        $specsValueID = SpecsValue::insert($specsValueName[$j]);
                        if(!$specsValueID){
                            $addSpecsValue = false;
                            break;
                        }
                    }
                }
                if($addSpecsKey && $addSpecsValue){
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
        }else{
            return $this->error(__('web.name_duplication'));
        }
    }

    /**
     * 編輯規格分類
     * @param $specsKey
     * @param $specsSort
     * @return array
     */
    public function editSpecsSortWeb($specsKey,$specsSort){
        DB::beginTransaction();
        $hasName = $this->where(function ($query) use ($specsSort){
            $query->where('specs_sort_name',$specsSort['specs_sort_name'])->where('status','<>',-1)->where('specs_sort_id','<>',$specsSort['specs_sort_id']);
        })->orWhere(function ($q) use ($specsSort){
            $q->where('en_specs_sort_name',$specsSort['en_specs_sort_name'])->where('status','<>',-1)->where('specs_sort_id','<>',$specsSort['specs_sort_id']);
        })->first();
        $updateSpecsSort = true;
        if(empty($hasName)){
            $specsSortInfo = $this->where('specs_sort_id',$specsSort['specs_sort_id'])->select('specs_sort_id','specs_sort_name','en_specs_sort_name','status')->first()->toArray();
            $isEqual = self::isEqualArray($specsSort,$specsSortInfo);
            if(!$isEqual){
                $updateSpecsSort = $this->where('specs_sort_id',$specsSort['specs_sort_id'])->update($specsSort);
            }
            if($updateSpecsSort){
                $specsKeyDelete = true;
                $specsValueDelete = true;
                $specsKeyInfo = SpecsKey::where('specs_sort_id',$specsSort['specs_sort_id'])->select('specs_key_id')->get()->toArray();
                if(!empty($specsKeyInfo)){
                    if(!empty(SpecsValue::whereIn('specs_key_id',$specsKeyInfo)->get()->toArray())){
                        $specsValueDelete = SpecsValue::whereIn('specs_key_id',$specsKeyInfo)->delete();
                    }
                    $specsKeyDelete = SpecsKey::whereIn('specs_key_id',$specsKeyInfo)->delete();
                }
                if($specsValueDelete && $specsKeyDelete){
                    $addSpecsKey = true;
                    $addSpecsValue = true;
                    $specsKeyID = [];
                    for($i=0;$i<count($specsKey);$i++){
                        $specsKeyName[$i]['specs_key_name'] = $specsKey[$i]['specs_key_name'];
                        $specsKeyName[$i]['en_specs_key_name'] = $specsKey[$i]['en_specs_key_name'];
                        $specsKeyName[$i]['specs_sort_id'] = $specsSort['specs_sort_id'];
                        $specsKeyID[$i] = SpecsKey::insertGetId($specsKeyName[$i]);
                        if(empty($specsKeyID[$i])){
                            $addSpecsKey = false;
                            break;
                        }
                        for($j=0;$j<count($specsKey[$i]['specs_value']);$j++){
                            $specsValueName[$j]['specs_value_name'] = $specsKey[$i]['specs_value'][$j]['specs_value_name'];
                            $specsValueName[$j]['en_specs_value_name'] = $specsKey[$i]['specs_value'][$j]['en_specs_value_name'];
                            $specsValueName[$j]['specs_key_id'] = $specsKeyID[$i];
                            $specsValueID = SpecsValue::insert($specsValueName[$j]);
                            if(!$specsValueID){
                                $addSpecsValue = false;
                                break;
                            }
                        }
                    }
                    if($addSpecsKey && $addSpecsValue){
                        DB::commit();
                        return $this->success(__('web.success'));
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
        }else{
            return $this->error(__('web.name_duplication'));
        }

    }

    /**
     * 删除規格分類
     * @param $specsSortID
     * @return array
     */
    public function deleteSpecsSortWeb($specsSortID){
        DB::beginTransaction();
        $specsKeyDelete = true;
        $specsValueDelete = true;
        $specsKeyInfo = SpecsKey::whereIn('specs_sort_id',$specsSortID)->select('specs_key_id')->get()->toArray();
        if(!empty($specsKeyInfo)){
            if(!empty(SpecsValue::whereIn('specs_key_id',$specsKeyInfo)->get()->toArray())){
                $specsValueDelete = SpecsValue::whereIn('specs_key_id',$specsKeyInfo)->delete();
            }
            $specsKeyDelete = SpecsKey::whereIn('specs_key_id',$specsKeyInfo)->delete();

        }
        $specsSortDelete = SpecsSort::whereIn('specs_sort_id',$specsSortID)->delete();


        if($specsSortDelete && $specsKeyDelete && $specsValueDelete){
            DB::commit();
            return $this->success(__('web.delete_success'));
        }else{
            DB::rollBack();
            return $this->error(__('web.delete_fail'));
        }
    }

    /**
     * 獲取所有規格分類
     * @return array
     */
    public function getAllSpecsSortWeb(){
        $where[] = array('specs_sort.status','=',1);
        $specs = $this->with(['specsKey'=>function($query){
            $query->with(['specsValue'=>function($q){
                $q->select('specs_value_id','specs_key_id','specs_value_name','en_specs_value_name');
            }])->select('specs_key_id','specs_sort_id','specs_key_name','en_specs_key_name');
        }])
            ->where($where)
            ->select('specs_sort_id','specs_sort_name','en_specs_sort_name')
            ->get()->toArray();
        return $specs;
    }

}
