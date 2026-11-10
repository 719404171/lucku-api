<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/7/1
 * Time: 10:45
 */

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

class File extends Base
{
    use Notifiable;
    protected $table = 'file';
    protected $primaryKey = 'file_id';
    public $timestamps = false;

    /**
     * 新增文件
     * @param $data
     * @return array
     */
    public function addFileWeb($data){
        $addFile = $this->insert($data);
        if($addFile){
            return $this->success(__('web.add_success'));
        }else{
            return $this->error(__('web.add_fail'));
        }
    }

    /**
     * 獲取文件列表
     * @param $data
     * @return array
     */
    public function getFileListWeb($data){
        $limit = $data['limit'];
        $page = ($data['page'] - 1) * $limit;
        $where = [];
        if(!empty($data['file_sort_id'])){
            $where['file_sort_id'] =  $data['file_sort_id'];
        }
        $file['data'] = $this->where($where)
            ->offset($page)->limit($limit)
            ->get()->toArray();
        $file['count'] = $this->where($where)->count();
        return $this->success(__('web.success'),$file);
    }

}
