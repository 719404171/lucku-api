<?php
/*
 * File: MenusController.php
 * Project: NewDemo
 * Created Time: 2024-07-01 02:31:25 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-07-24 4:19:32 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2024 成都睿創科技有限公司
 */


namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\Request;

class MenusController extends Controller
{
    protected $Menu;

    public function __construct()
    {
        $this->Menu = new Menu();
    }

    /**
     * 获取所有菜单和權限
     * @param Request $request
     * @return array
     */
    public function getAllMenuWeb(Request $request){
        $language = self::getLanguageWeb();
        $data = $this->Menu->getAllMenuWeb($language);
        return $this->success(__('web.success'),$data);
    }

}
