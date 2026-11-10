<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/6/24
 * Time: 10:57
 */

namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use App\Models\SpecsKey;
use App\Models\SpecsSort;
use App\Models\SpecsValue;
use Illuminate\Http\Request;

class SpecsController extends Controller
{
    protected $SpecsKey;
    protected $SpecsSort;

    public function __construct()
    {
        $this->SpecsKey = new SpecsKey();
        $this->SpecsSort = new SpecsSort();
    }

    /**
     * 獲取規格分類列表
     * @param Request $request
     * @return array
     */
    public function getSpecsSortListWeb(Request $request){
        $data = $request->post();
        $language = self::getLanguageWeb();
        $datas = $this->SpecsSort->getSpecsSortListWeb($data,$language);
        return $this->success(__('web.success'),$datas);
    }

    /**
     * 新增規格分類
     * @param Request $request
     * @return array
     */
    public function addSpecsSortWeb(Request $request){
        $specsKey = $request->post('specs_key');
        $specsSortName = $request->post('specs_sort_name');
        $enSpecsSortName = $request->post('en_specs_sort_name');
        $data = $this->SpecsSort->addSpecsSortWeb($specsKey,$specsSortName,$enSpecsSortName);
        return $data;
    }

    /**
     * 編輯規格分類
     * @param Request $request
     * @return array
     */
    public function editSpecsSortWeb(Request $request){
        $specsKey = $request->post('specs_key');
        $specsSort = $request->except(['specs_key']);
        $datas = $this->SpecsSort->editSpecsSortWeb($specsKey,$specsSort);
        return $datas;
    }

    /**
     * 删除規格分類
     * @param Request $request
     * @return array
     */
    public function deleteSpecsSortWeb(Request $request){
        $specsSortID = $request->post('specs_sort_id');
        $data = $this->SpecsSort->deleteSpecsSortWeb($specsSortID);
        return $data;
    }

    /**
     * 獲取所有規格分類
     * @return array
     */
    public function getAllSpecsSortWeb(){
        $data = $this->SpecsSort->getAllSpecsSortWeb();
        return $this->success(__('web.success'),$data);
    }


}
