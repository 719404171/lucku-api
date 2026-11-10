<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/7/4
 * Time: 15:06
 */

namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use App\Models\UserReservation;
use Illuminate\Http\Request;

class UserReservationController extends Controller
{
    protected $UserReservation;

    public function __construct()
    {
        $this->UserReservation = new UserReservation();
    }

    /**
     * 獲取用戶排號列表
     * @param Request $request
     * @return array
     */
    public function getUserReservationWeb(Request $request){
        $data = $request->post();
        $language = self::getLanguageWeb();
        $datas = $this->UserReservation->getUserReservationWeb($data,$language);
        return $datas;
    }

    /**
     * 修改用戶排號狀態
     * @param Request $request
     * @return array
     */
    public function editUserReservationWeb(Request $request){
        $data = $request->post();
        $datas = $this->UserReservation->editUserReservationWeb($data);
        return $datas;
    }

    /**
     * 刪除用戶排號記錄
     * @param Request $request
     * @return array
     */
    public function deleteUserReservationWeb(Request $request){
        $user_reservation_id = $request->post('user_reservation_id');
        $data = $this->UserReservation->deleteUserReservationWeb($user_reservation_id);
        return $data;
    }

}
