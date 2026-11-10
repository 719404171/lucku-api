<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/8/24
 * Time: 12:00
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserAgreement;
use Illuminate\Http\Request;

class UserAgreementController extends Controller
{
    protected $UserAgreement;

    public function __construct()
    {
        $this->UserAgreement = new UserAgreement();
    }

    /**
     * 獲取用户协议
     * @return array
     */
    public function getUserAgreementWeb(){
        $data = UserAgreement::where('ua_id',1)->first();
        if(empty($data)){
            $data = [];
        }
        return $this->success(__('web.success'),$data);
    }

    /**
     * 修改用户协议
     * @param Request $request
     * @return array
     */
    public function editUserAgreementWeb(Request $request){
        $data = $this->UserAgreement->editUserAgreementWeb($request->post());
        return $data;
    }

}
