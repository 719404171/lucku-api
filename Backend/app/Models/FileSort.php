<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/7/1
 * Time: 10:46
 */

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

class FileSort extends Base
{
    use Notifiable;
    protected $table = 'file_sort';
    protected $primaryKey = 'file_sort_id';
    public $timestamps = false;

    /**
     * 關聯文件表
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function file(){
        return $this->hasMany('App\Models\File', 'file_sort_id', 'file_sort_id');
    }

    /**
     * 新增/編輯文件分類
     * @param $data
     * @return array
     */
    public function handleFileSortWeb($data){
        $data = $this->updateOrInsert(['file_sort_id'=>$data['file_sort_id']], $data);
        if($data){
            return $this->success(__('web.success'));
        }else{
            return $this->error(__('web.network_error'));
        }
    }

    /**
     * 刪除文件分類
     * @param $fileSortID
     * @return array
     */
    public function deleteFileSortWeb($fileSortID){
        DB::beginTransaction();
        $deleteFile = true;
        $deleteFileSort = $this->where('file_sort_id',$fileSortID)->delete();
        if(File::where('file_sort_id',$fileSortID)->count() > 0){
            $deleteFile = File::where('file_sort_id',$fileSortID)->delete();
        }
        if($deleteFile && $deleteFileSort){
            DB::commit();
            return $this->success(__('web.delete_success'));
        }else{
            DB::rollBack();
            return $this->error(__('web.delete_fail'));
        }
    }

}
