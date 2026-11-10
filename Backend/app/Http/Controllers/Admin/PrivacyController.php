<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/7/1
 * Time: 16:37
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Privacy;
use Illuminate\Http\Request;

class PrivacyController extends Controller
{
    protected $Privacy;

    public function __construct()
    {
        $this->Privacy = new Privacy();
    }

    /**
     * 獲取隱私協議
     * @return array
     */
    public function getPrivacyWeb(){
        $data = Privacy::where('privacy_id',1)->first();
        if(empty($data)){
            $data = [];
        }
        return $this->success(__('web.success'),$data);
    }

    /**
     * 修改隱私協議
     * @param Request $request
     * @return array
     */
    public function editPrivacyWeb(Request $request){
        $data = $this->Privacy->editPrivacyWeb($request->post());
        return $data;
    }

}
