<?php
/*
 * File: ProductController.php
 * Project: NewDemo
 * Created Time: 2026-06-04 04:11:10 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2026-07-02 9:56:40 am
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2026 成都睿創科技有限公司
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductSpecsKey;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    protected $Product;
    protected $ProductSpecsKey;

    public function __construct()
    {
        $this->Product = new Product();
        $this->ProductSpecsKey = new ProductSpecsKey();
    }

    /**
     * 獲取產品列表
     * @param Request $request
     * @return array
     */
    public function getProductListWeb(Request $request){
        $data = $request->post();
        $language = self::getLanguageWeb();
        $datas = $this->Product->getProductListWeb($data,$language);
        return $this->success(__('web.success'),$datas);
    }

    /**
     * 添加產品
     * @param Request $request
     * @return array
     */
    public function addProductWeb(Request $request){
        $album = $request->post('album');
        $tag = $request->post('tag');
        $product = $request->except(['album','tag']);
        $token = $request->header('TOKEN');
        $data = $this->Product->addProductWeb($album,$tag,$product,$token);
        return $data;
    }

    /**
     * 編輯產品
     * @param Request $request
     * @return array
     */
    public function editProductWeb(Request $request){
        $token = $request->header('TOKEN');
        $album = $request->post('album');
        $tag = $request->post('tag');
        $product = $request->except(['album','tag']);
        $data = $this->Product->editProductWeb($album,$tag,$product,$token);
        return $data;
    }

    /**
     * 刪除產品
     * @param Request $request
     * @return array
     */
    public function deleteProductWeb(Request $request){
        $productID = $request->post('product_id');
        $data = $this->Product->deleteProductWeb($productID);
        return $data;
    }

    /**
     * 獲取產品規格
     * @param Request $request
     * @return array
     */
    public function getProductSpecsWeb(Request $request){
        $productID = $request->post('product_id');
        $data = $this->Product->getProductSpecsWeb($productID);
        return $data;
    }

    /**
     * 添加產品規格組合
     * @param Request $request
     * @return array
     */
    public function addProductSpecsKeyWeb(Request $request){
        $specsKey = $request->post('specs_key');
        $productID = $request->post('product_id');
        $language = self::getLanguageWeb();
        $datas = $this->ProductSpecsKey->addProductSpecsKeyWeb($specsKey,$productID,$language);
        return $datas;
    }

    /**
     * 編輯產品規格
     * @param Request $request
     * @return array
     */
    public function editProductSpecsWeb(Request $request){
        $token = $request->header('TOKEN');
        $productRelation = $request->post('product_relation');
        $product = $request->except(['product_relation']);
        $data = $this->Product->editProductSpecsWeb($productRelation,$product,$token);
        return $data;
    }

    /**
     * 獲取所有產品及規格
     * @return array
     */
    public function getAllProductWeb(){
        $language = self::getLanguageWeb();
        $data = $this->Product->getAllProductWeb($language);
        return $this->success(__('web.success'),$data);
    }

}
