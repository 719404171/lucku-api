<?php
/*
 * File: LoginController.php
 * Project: NewDemo
 * Created Time: 2024-05-30 09:52:25 am
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2026-06-25 4:20:56 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2024 成都睿創科技有限公司
 */



namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Tests\CreatesApplication;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Api\BaseController;

class LoginController extends Controller{

    private $Users;
    public function __construct()
    {
        $this->Users = new User();
    }

    /**
     * @param Request $request
     * @return array
     * 用戶登錄
     */
    public function login(Request $request){
        if (!$request->has("user_phone")) {
            return $this->error(__('api.phone_missing'));
        } else if (!$request->has("user_password")) {
            return $this->error(__('api.password_missing'));
        } else {
            // 臨時封禁到期自動解封，避免解封時間已過仍無法登錄
            $this->Users->autoUnbanExpired(['user_phone' => $request->post('user_phone'), 'area_code' => $request->post('area_code')]);
            // 传递给attempt的数组只是一个数组；它与您的数据库表无关。您不必使用'user_phone'和'user_password'。
            if (Auth::attempt(['user_phone' => request('user_phone'), 'password' =>request('user_password') ,'area_code' => request('area_code'),'user_status' => 1])) {
                $user = Auth::user();
                $token = $user->createToken($user['user_id'])->accessToken;
                //修改登錄信息
                $addInfo = User::where('user_id', $user['user_id'])
                               ->update([ 'token_type' => $request->post('token_type'), 
                                          'client_id' => $request->post('client_id'),
                                          'login_type' => 1,
                                          'last_login_ip' => $request->ip(),
                                          'finally_at' => date('Y-m-d H:i:s') ]);
                if ($addInfo) {
                    //登錄次數加一
                    User::where('user_id', $user['user_id'])->increment('login_time');
                    // 獲取會員等級 + 今日獎勵數量（今日是否已在簽到時領取過）
                    $bonusInfo = $this->Users->getLoginBonusInfo($user['user_id']);
                    $results['token'] = $token;
                    $results['user_email'] = $user['user_email'];
                    $results['user_name'] = $user['user_name'];
                    $results['user_phone'] = $user['user_phone'];
                    $results['user_id'] = $user['user_id'];
                    $results['area_code'] = $user['area_code'];
                    $results['member_name'] = $bonusInfo['member_name'];
                    $results['member_grade'] = $bonusInfo['member_grade'];
                    $results['bonus_coin'] = $bonusInfo['bonus_coin'];
                    $results['bonus_diamond'] = $bonusInfo['bonus_diamond'];
                    $results['today_bonus_coin'] = $bonusInfo['today_bonus_coin'];
                    $results['today_bonus_diamond'] = $bonusInfo['today_bonus_diamond'];
                    return $this->success(__('api.login_success') , $results);  
                } else {
                    return $this->error(__('api.login_error'));
                }
            } else {
                // 賬號被封禁時給出明確提示（user_status 被置 0 且帶封禁標記）
                $banned = User::where('user_phone', $request->post('user_phone'))
                    ->where('area_code', $request->post('area_code'))
                    ->select('user_status', 'ban_until', 'ban_reason')
                    ->first();
                if (!empty($banned) && (int)$banned->user_status !== 1 && !empty($banned->ban_until) && strtotime($banned->ban_until) > time()) {
                    return $this->error(__('api.account_banned') . ' ' . $banned->ban_until);
                }
                return $this->error(__('api.login_error'));
            }
        }
    }





    /**
     * @param Request $request
     * @return array
     * 郵箱登錄
     */
    public function loginEmail(Request $request){
        if (!$request->has("user_email")) {
            return $this->error(__('api.email_missing'));
        } else if (!$request->has("user_password")) {
            return $this->error(__('api.password_missing'));
        }

        $email = trim((string)$request->post('user_email'));
        if ($email === '') {
            return $this->error(__('api.email_missing'));
        }

        // 臨時封禁到期自動解封，避免解封時間已過仍無法登錄
        $this->Users->autoUnbanExpired(['user_email' => $email]);

        // 歷史數據中同一郵箱可能有多條（舊版可重複寫入），逐條校驗密碼，
        // 避免取到密碼不同的那條而誤報「用戶名或密碼錯誤」
        $matched = null;
        foreach (User::where('user_email', $email)->orderBy('user_id')->get() as $candidate) {
            if (Hash::check((string)$request->post('user_password'), (string)$candidate->user_password)) {
                $matched = $candidate;
                break;
            }
        }
        if (empty($matched)) {
            return $this->error(__('api.login_error'));
        }

        // 提示詞按真實原因返回：封禁 > 郵箱未驗證 > 賬號密碼錯誤
        $status = (int)$matched->user_status;
        if ($status !== 1) {
            if (!empty($matched->ban_until) || !empty($matched->ban_reason)) {
                $tip = !empty($matched->ban_until) ? __('api.account_banned') : __('api.account_disabled');
                if (!empty($matched->ban_until)) {
                    $tip .= ' ' . $matched->ban_until;
                }
                if (!empty($matched->ban_reason)) {
                    $tip .= ' ' . $matched->ban_reason;
                }
                return $this->error($tip);
            }
            if ($status === 0) {
                return $this->error(__('api.Email_no_verified'));
            }
            return $this->error(__('api.login_error'));
        }

        $userId = (int)$matched->user_id;
        $token = $matched->createToken($userId)->accessToken;
        $addInfo = User::where('user_id', $userId)
                       ->update([ 'token_type' => $request->post('token_type'),
                                  'client_id' => $request->post('client_id'),
                                  'login_type' => 4,
                                  'last_login_ip' => $request->ip(),
                                  'finally_at' => date('Y-m-d H:i:s') ]);
        if (!$addInfo) {
            return $this->error(__('api.login_error'));
        }

        User::where('user_id', $userId)->increment('login_time');
        // 獲取會員等級 + 今日獎勵數量（今日是否已在簽到時領取過）
        $bonusInfo = $this->Users->getLoginBonusInfo($userId);

        $results['token'] = $token;
        $results['user_email'] = $matched->user_email;
        $results['user_name'] = $matched->user_name;
        $results['user_phone'] = $matched->user_phone;
        $results['user_integral'] = $matched->user_integral;
        $results['user_id'] = $userId;
        $results['area_code'] = $matched->area_code;
        $results['member_name'] = $bonusInfo['member_name'];
        $results['member_grade'] = $bonusInfo['member_grade'];
        $results['bonus_coin'] = $bonusInfo['bonus_coin'];
        $results['bonus_diamond'] = $bonusInfo['bonus_diamond'];
        $results['today_bonus_coin'] = $bonusInfo['today_bonus_coin'];
        $results['today_bonus_diamond'] = $bonusInfo['today_bonus_diamond'];
        return $this->success(__('api.login_success') ,  $results);
    }





/**********************************************************END**********************************************************************/
}
