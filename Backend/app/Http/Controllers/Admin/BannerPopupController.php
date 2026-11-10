<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BannerPopup;
use Illuminate\Http\Request;

class BannerPopupController extends Controller
{
    protected $BannerPopup;

    public function __construct()
    {
        $this->BannerPopup = new BannerPopup();
    }

    public function getBannerPopupWeb(Request $request){
        return $this->BannerPopup->getBannerPopupWeb();
    }

    public function handleBannerPopupWeb(Request $request){
        $data = $request->post();
        return $this->BannerPopup->handleBannerPopupWeb($data);
    }
}
