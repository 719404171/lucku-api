<?php
/*
 * File: ProductSortController.php
 * Project: NewDemo
 * Created Time: 2026-06-01 02:34:39 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2026-06-03 1:48:57 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2026 成都睿創科技有限公司
 */



namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductSort;
use Illuminate\Http\Request;
class ProductSortController extends Controller
{
    protected $ProductSort;

    public function __construct()
    {
        $this->ProductSort = new ProductSort();
    }

    /**
     * 獲取分類列表
     * @param Request $request
     * @return array
     */
    public function getProductSortListWeb(Request $request){
        $data = $request->post();
        $language = self::getLanguageWeb();
        $datas = $this->ProductSort->getProductSortListWeb($data,$language);
        return $this->success(__('web.success'),$datas);
    }

    /**
     * 添加/編輯產品分類
     * @param Request $request
     * @return array
     */
    public function handleProductSortListWeb(Request $request){
        $data = $request->post();
        $datas = $this->ProductSort->handleProductSortListWeb($data);
        return $datas;
    }

    /**
     * 刪除產品分類
     * @param Request $request
     * @return array
     */
    public function deleteProductSortListWeb(Request $request){
        $productSortID = $request->post('product_sort_id');
        $data = $this->ProductSort->deleteProductSortListWeb($productSortID);
        return $data;
    }

    /**
     * 獲取所有分類列表
     * @return array
     */
    public function getAllProductSortWeb(){
        $language = self::getLanguageWeb();
        $data = $this->ProductSort->getAllProductSortWeb($language);
        return $data;
    }

    /**
     * 獲取所有分類商品
     * @param Request $request
     * @return array|null|string
     */
    public function getAllSortProductWeb(Request $request){
        $data = $request->post();
        $language = self::getLanguageWeb();
        $data = $this->ProductSort->getAllSortProductWeb($data,$language);
        return $data;
    }

}
