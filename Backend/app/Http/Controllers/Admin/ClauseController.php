<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/7/1
 * Time: 16:22
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Clause;
use Illuminate\Http\Request;

class ClauseController extends Controller
{
    protected $Clause;

    public function __construct()
    {
        $this->Clause = new Clause();
    }

    /**
     * 獲取用戶協議
     * @return array
     */
    public function getClauseWeb(){
        $data = Clause::where('clause_id',1)->first();
        if(empty($data)){
            $data = [];
        }
        return $this->success(__('web.success'),$data);
    }

    /**
     * 修改用戶協議
     * @param Request $request
     * @return array
     */
    public function editClauseWeb(Request $request){
        $data = $this->Clause->editClauseWeb($request->post());
        return $data;
    }

}
