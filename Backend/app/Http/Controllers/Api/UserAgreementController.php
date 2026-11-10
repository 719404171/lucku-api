<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/8/31
 * Time: 12:00
 */

namespace App\Http\Controllers\Api;

use App\Models\UserAgreement;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserAgreementController extends Controller
{
    private $UserAgreement;

    public function __construct()
    {
        $this->UserAgreement = new UserAgreement();
    }

    /**
     * 獲取用户协议
     * @param Request $request
     * @return array
     */
    public function getUserAgreementApi(Request $request){
        return $this->UserAgreement->getUserAgreementApi();
    }
}
