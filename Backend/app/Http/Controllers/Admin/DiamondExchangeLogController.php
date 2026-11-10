<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiamondCoinExchangeLog;
use Illuminate\Http\Request;

class DiamondExchangeLogController extends Controller
{
    private $DiamondCoinExchangeLog;

    public function __construct()
    {
        $this->DiamondCoinExchangeLog = new DiamondCoinExchangeLog();
    }

    /**
     * 獲取金幣兌換記錄列表
     */
    public function getListWeb(Request $request)
    {
        $data = $request->post();
        $datas = $this->DiamondCoinExchangeLog->getListWeb($data);
        return $this->success(__('web.success'), $datas);
    }
}
