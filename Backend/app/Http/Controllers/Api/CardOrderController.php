<?php
/*
 * File: CardOrderController.php
 * Project: NewDemo
 * Created Time: 2024-07-12 03:00:30 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2026-06-03 5:40:56 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2024 成都睿創科技有限公司
 */
namespace App\Http\Controllers\Api;


use App\Models\CardOrder;
use App\Models\CardOrderDetails;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CardOrderController extends Controller{
    
    private $CardOrder;
    public function __construct()
    {
        $this->CardOrder = new CardOrder();
        $this->CardOrderDetails = new CardOrderDetails();
    }

}
