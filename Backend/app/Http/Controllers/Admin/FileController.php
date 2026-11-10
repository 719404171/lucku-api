<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/7/1
 * Time: 10:31
 */

namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\FileSort;
use Illuminate\Http\Request;

class FileController extends Controller
{
    protected $File;
    protected $FileSort;

    public function __construct()
    {
        $this->File = new File();
        $this->FileSort = new FileSort();
    }

    /**
     * 獲取文件列表
     * @param Request $request
     * @return array
     */
    public function getFileListWeb(Request $request){
        $data = $request->post();
        $datas = $this->File->getFileListWeb($data);
        return $datas;
    }

    /**
     * 獲取分类列表
     * @return array
     */
    public function getFileSortListWeb(){
        $data = FileSort::get()->toArray();
        return $this->success(__('web.success'),$data);
    }

    /**
     * 新增/編輯文件分類
     * @param Request $request
     * @return array
     */
    public function handleFileSortWeb(Request $request){
        $data = $request->post();
        $datas = $this->FileSort->handleFileSortWeb($data);
        return $datas;
    }

    /**
     * 刪除文件分類
     * @param Request $request
     * @return array
     */
    public function deleteFileSortWeb(Request $request){
        $fileSortID = $request->post('file_sort_id');
        $data = $this->FileSort->deleteFileSortWeb($fileSortID);
        return $data;
    }

    /**
     * 新增文件
     * @param Request $request
     * @return array
     */
    public function addFileWeb(Request $request){
        $data = $request->post();
        $datas = $this->File->addFileWeb($data);
        return $datas;
    }

    /**
     * 刪除文件
     * @param Request $request
     * @return array
     */
    public function deleteFileWeb(Request $request){
        $fileID = $request->post('file_id');
        $data = File::whereIn('file_id',$fileID)->delete();
        if($data){
            return $this->success(__('web.delete_success'));
        }else{
            return $this->error(__('web.delete_fail'));
        }
    }

}
