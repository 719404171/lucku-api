<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BannerPopup;

class BannerPopupController extends Controller
{
    protected $BannerPopup;

    public function __construct()
    {
        $this->BannerPopup = new BannerPopup();
    }

    public function getBannerPopupApi(){
        return $this->BannerPopup->getBannerPopupApi();
    }
}
