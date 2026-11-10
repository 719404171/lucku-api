<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/7/4
 * Time: 14:47
 */

namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use App\Models\ReservationProject;
use Illuminate\Http\Request;

class ReservationProjectController extends Controller
{
    protected $ReservationProject;

    public function __construct()
    {
        $this->ReservationProject = new ReservationProject();
    }

    /**
     * 獲取預約項目列表
     * @param Request $request
     * @return array
     */
    public function getReservationProjectWeb(Request $request){
        $data = $request->post();
        $language = self::getLanguageWeb();
        $datas = $this->ReservationProject->getReservationProjectWeb($data,$language);
        return $datas;
    }

    /**
     * 添加/編輯預約項目
     * @param Request $request
     * @return array
     */
    public function handleReservationProjectWeb(Request $request){
        $data = $request->post();
        $state = $this->ReservationProject->handleReservationProjectWeb($data);
        return $state;
    }

    /**
     * 刪除預約項目
     * @param Request $request
     * @return array
     */
    public function deleteReservationProjectWeb(Request $request){
        $ProjectID = $request->post('reservation_project_id');
        $data = $this->ReservationProject->deleteReservationProjectWeb($ProjectID);
        return $data;
    }

}
