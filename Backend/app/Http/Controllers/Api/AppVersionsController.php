<?php
/*
 * File: AppVersionsController.php
 * Project: NewDemo
 * Created Time: 2024-06-21 02:45:57 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-07-15 5:09:47 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2024 成都睿創科技有限公司
 */



namespace App\Http\Controllers\Api;

use App\Models\AppVersions;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\CreatesApplication;

class AppVersionsController extends Controller
{
    private $AppVersions;
    public function __construct()
    {
        $this->AppVersions = new AppVersions();
    }


    /**
     * @param Request $request
     * @return array
     * 獲取APP版本
     */
    public function getAppVersions(){
        $results = $this->AppVersions->getAppVersions();
        return $results;
    }


}
