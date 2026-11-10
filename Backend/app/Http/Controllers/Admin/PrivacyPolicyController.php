<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/8/24
 * Time: 12:00
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PrivacyPolicy;
use Illuminate\Http\Request;

class PrivacyPolicyController extends Controller
{
    protected $PrivacyPolicy;

    public function __construct()
    {
        $this->PrivacyPolicy = new PrivacyPolicy();
    }

    /**
     * 獲取隐私政策
     * @return array
     */
    public function getPrivacyPolicyWeb(){
        $data = PrivacyPolicy::where('pp_id',1)->first();
        if(empty($data)){
            $data = [];
        }
        return $this->success(__('web.success'),$data);
    }

    /**
     * 修改隐私政策
     * @param Request $request
     * @return array
     */
    public function editPrivacyPolicyWeb(Request $request){
        $data = $this->PrivacyPolicy->editPrivacyPolicyWeb($request->post());
        return $data;
    }

}
