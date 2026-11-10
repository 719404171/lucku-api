<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/8/31
 * Time: 12:00
 */

namespace App\Http\Controllers\Api;

use App\Models\PrivacyPolicy;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PrivacyPolicyController extends Controller
{
    private $PrivacyPolicy;

    public function __construct()
    {
        $this->PrivacyPolicy = new PrivacyPolicy();
    }

    /**
     * 獲取隐私政策
     * @param Request $request
     * @return array
     */
    public function getPrivacyPolicyApi(Request $request){
        return $this->PrivacyPolicy->getPrivacyPolicyApi();
    }
}
