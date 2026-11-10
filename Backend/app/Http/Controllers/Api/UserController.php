<?php
/*
 * File: UserController.php
 * Project: NewDemo
 * Created Time: 2024-05-29 11:27:52 am
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2026-09-08 4:55:21 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2024 成都睿創科技有限公司
 */

namespace App\Http\Controllers\Api;

use App\Models\CheckIn;
use App\Models\User;
use Illuminate\Http\Request;
use Tests\CreatesApplication;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Api\BaseController;

class UserController extends Controller{

    private $Users;
    public function __construct()
    {
        $this->Users = new User();
    }

    /**
     * @param Request $request
     * @return array
     * 郵箱驗證碼重置密碼
     */
    public function resetPasswordByEmail(Request $request){
        $email = $request->post('email');
        $code = $request->post('code');
        $password = $request->post('password');

        if (empty($email) || empty($code) || empty($password)) {
            return $this->error(__('api.param_error'));
        }

        // 驗證驗證碼
        $record = DB::table('email_verify_codes')
            ->where('email', $email)
            ->where('code', $code)
            ->where('used', 0)
            ->where('expires_at', '>', date('Y-m-d H:i:s'))
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$record) {
            return $this->error('驗證碼無效或已過期');
        }

        DB::beginTransaction();
        try {
            // 標記驗證碼為已使用
            DB::table('email_verify_codes')
                ->where('id', $record->id)
                ->update(['used' => 1, 'updated_at' => date('Y-m-d H:i:s')]);

            // 更新密碼
            $updated = DB::table('users')
                ->where('user_email', $email)
                ->update(['user_password' => bcrypt($password), 'updated_at' => date('Y-m-d H:i:s')]);

            if ($updated) {
                DB::commit();
                return $this->success(__('api.edit_success'));
            } else {
                DB::rollBack();
                return $this->error(__('api.edit_fail'));
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error(__('web.edit_fail'), $e->getMessage());
        }
    }

    /**
     * @param Request $request
     * @return array
     * 獲取個人信息
     */
    public function getUsersInfo(Request $request){
        $user = auth('api')->user();
        $user_id=$user->user_id;
        if (empty($user_id)) {
            return $this->success(__('api.user_notexist'));
        }
        $results = $this->Users->getUsersInfo($user_id);
        return $results;

    }





    /**
     * @param Request $request
     * @return array
     * 忘記/修改密碼
     */
    public function forgetPass(Request $request){
        if (!$request->has("user_phone")) {
            return $this->error(__('api.phone_missing'));
        } else if (!$request->has("user_password")) {
            return $this->error(__('api.password_missing'));
        } else {
            DB::beginTransaction();
            try {
                $data = User::where('user_phone',$request->post("user_phone"))->where('area_code',$request->post('area_code'))->first();
                if(!empty($data)){
                    $password = bcrypt($request->post('user_password'));
                    $user = User::where('user_phone', $request->post('user_phone'))->where('area_code',$request->post('area_code'))->update(['user_password' => $password]);
                    if ($user) {
                        DB::commit();
                        return $this->success(__('api.edit_success'));
                    } else {
                        DB::rollBack();
                        return $this->error(__('api.edit_fail'));
                    }
                }else{
                    DB::rollBack();
                    return $this->error(__('api.phone_notexist'));
                }
            }catch (\Exception $e) {
                DB::rollBack();
                return $this->error( __('web.edit_fail') , $e->getMessage());
            }
        }
    }



    /**
     * @param Request $request
     * @return array
     * 修改用戶信息
     */
    public function UpdateUsers(Request $request){
        if(empty($request->post())){
            return $this->parametersError(__('api.param_error'));
        }else{
            $user = auth('api')->user();
            $user_id=$user->user_id;
            $data = $request->post();
            // 資產 / 身份 / 權限類字段禁止由用戶自行修改，避免自刷钻石、自我解封、改綁第三方帳號
            unset($data['user_integral'], $data['user_integral_all'], $data['user_balance']);
            unset($data['user_id'], $data['user_coin'], $data['user_status']);
            unset($data['ban_until'], $data['ban_reason'], $data['open_id'], $data['stripe_id'], $data['login_type']);
            $results = $this->Users->UpdateUsers($user_id , $data);
            return $results;
        }
    }


    /**
     * @param Request $request
     * @return array
     * 修改綁定手機號
     */
    public function UpdatePhone(Request $request){
        if(empty($request->post('user_phone'))){
            return $this->parametersError(__('api.param_error'));
        }else{
            $user = auth('api')->user();
            $user_id=$user->user_id;
            $data = $request->post();
            $ExistPhone = DB::table('users')->where('user_phone',$data['user_phone'])->where('area_code',$data['area_code'])->where('user_status',1)->first();
            if(!empty($ExistPhone)){
                return $this->error(__('api.phone_exist'));
            }else{
                $results = $this->Users->UpdatePhone($user_id , $data);
                return $results;
            }
        }
    }



    /**
     * @param Request $request
     * @return array
     * 密碼驗證
     */
    public function PasswordVerify(Request $request){
        if (!$request->has("user_phone")) {
            return $this->error(__('api.phone_missing'));
        } else if (!$request->has("user_password")) {
            return $this->error(__('api.password_missing'));
        } else {
            // 传递给attempt的数组只是一个数组；它与您的数据库表无关。您不必使用'user_phone'和'user_password'。
            if (Auth::attempt(['user_phone' => request('user_phone'), 'password' =>request('user_password') ,'area_code' => request('area_code')])) {
                return $this->success(__('api.verify_success'));
            } else {
                return $this->error(__('api.verify_fail'));
            }
        }
    }


    /**
     * @param Request $request
     * @return array
     * 删除賬戶
     */
    public function deleteUser(Request $request){
        $user = auth('api')->user();
        $data['user_id']=$user->user_id;
        DB::beginTransaction();
        try {
            $results = DB::table('users')->where('user_id',$data['user_id'])->update(['user_status' => -1]);
            if($results){
                // 註銷後立即吊銷該用戶全部 token，避免舊 token 仍可訪問
                $user->tokens()->delete();
                DB::commit();
                return $this->success(__('api.delete_success'));
            }else{
                DB::rollBack();
                return $this->error(__('api.delete_fail'));
            }
        }catch (\Exception $e) {
            DB::rollBack();
            return $this->error( __('api.delete_fail') , $e->getMessage());
        }
    }






    /**
     * @param Request $request
     * @return array
     * 註銷賬戶
     */
    public function BannedUser(Request $request){
        $username = (string)$request->post('username');
        $password = (string)$request->post('password');
        $areaCode = $request->post('areaCode');
        if ($username === '' || $password === '') {
            return $this->error(__('api.param_error'));
        }
        // 帳號可為郵箱或手機號；手機號需比對區號（前端帶 '+' 前綴，入庫不帶）
        $query = User::query();
        if (empty($areaCode)) {
            $query->where('user_email', $username);
        } else {
            $query->where('user_phone', $username)->where('area_code', ltrim($areaCode, '+'));
        }
        $user = $query->first();
        // 必須校驗密碼，避免僅憑手機號/郵箱就註銷他人帳號
        if (empty($user) || !\Illuminate\Support\Facades\Hash::check($password, (string)$user->user_password)) {
            return $this->error(__('api.login_error'));
        }
        DB::beginTransaction();
        try {
            // 軟刪除（保留訂單等關聯數據），並吊銷該用戶全部 token
            $updated = User::where('user_id', $user->user_id)->update(['user_status' => -1]);
            if ($updated) {
                $user->tokens()->delete();
                DB::commit();
                return $this->success(__('api.delete_success'));
            }
            DB::rollBack();
            return $this->error(__('api.delete_fail'));
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error(__('api.delete_fail'), $e->getMessage());
        }
    }

    public function userCheckIn(Request $request){
        $user = auth('api')->user();
        $user_id=$user->user_id;
        $today = Carbon::today()->toDateString();

        // 1. 先檢查今日是否已領取過每日金幣/鑽石（以錢包流水為準，優先級最高）
        if ($this->Users->hasClaimedTodayBonus((int)$user_id)) {
            return $this->error(__('api.bonus_already_claimed'));
        }

        // 2. 再檢查是否已簽到（正常情況下簽到和領幣是一起的，但保留雙重保險）
        $existing = CheckIn::where('user_id', $user_id)
            ->whereDate('check_date', $today)
            ->first();

        if ($existing) {
            return $this->error(__('api.check_error'));
        }
        DB::beginTransaction();
        try {
            $add = CheckIn::insert([
                'user_id' => $user_id,
                'check_date' => $today,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            if (!$add) {
                DB::rollBack();
                return $this->error(__('api.network_error'));
            }

            // 簽到成功：根據會員等級發送每日獎勵（金幣/鑽石），寫入錢包流水
            // addLoginBonus 內部已做二次去重判斷
            // 注意：簽到次數以 check_in 表記錄為準（每天每用戶一條），users 表無 check_number 字段，不可 increment
            $this->Users->addLoginBonus($user_id);
            DB::commit();
            return $this->success(__('api.check_success'));
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('userCheckIn exception', [
                'user_id' => $user_id,
                'error'   => $e->getMessage(),
            ]);
            return $this->error(__('api.network_error'), $e->getMessage());
        }

    }

    /**
     * 鑽石兌換金幣（1:1）
     * @param Request $request
     * @return array
     */
    /**
     * 鑽石（user_coin）兌換為餘額（user_balance）
     * 接口原名 convertDiamondToCoin（歷史原因保留），實際是 鑽石→餘額
     * 比例 rate 來自 shop_setting.diamond_coin_rate（1 鑽石 = rate 餘額）
     */
    public function convertDiamondToCoin(Request $request){
        $user = auth('api')->user();
        if (empty($user) || empty($user->user_id)) {
            return $this->error(__('api.no_login'));
        }
        $userId = (int)$user->user_id;
        $diamondAmount = (int)$request->post('amount');
        if ($diamondAmount <= 0) {
            return $this->parametersError(__('api.param_error'));
        }

        // 讀取後台設置：兌換開關 + 兌換比例
        $rate = 1;
        $exchangeEnable = true;
        $shopSetting = DB::table('shop_setting')->where('shop_id', 1)->first();
        if ($shopSetting) {
            if (property_exists($shopSetting, 'diamond_exchange_enable')) {
                $exchangeEnable = (int)$shopSetting->diamond_exchange_enable === 1;
            }
            if (property_exists($shopSetting, 'diamond_coin_rate')) {
                $rate = (float)$shopSetting->diamond_coin_rate;
            }
        }
        if (!$exchangeEnable) {
            return $this->error(__('api.diamond_exchange_disabled', [], '鑽石兌換功能已關閉'));
        }
        if ($rate <= 0) {
            $rate = 1;
        }

        DB::beginTransaction();
        try {
            // 行鎖查詢，防止並發重複扣減
            $userInfo = User::where('user_id', $userId)->lockForUpdate()->first();
            if (empty($userInfo)) {
                DB::rollBack();
                return $this->error(__('api.user_notexist'));
            }
            if ((int)$userInfo->user_coin < $diamondAmount) {
                DB::rollBack();
                return $this->error(__('api.insufficient_balance', [], '鑽石餘額不足'));
            }

            $balanceAmount = round($diamondAmount * $rate, 2);

            // 扣減鑽石（user_coin）、增加餘額（user_balance）
            User::where('user_id', $userId)
                ->where('user_coin', '>=', $diamondAmount)
                ->update([
                    'user_coin'     => DB::raw('user_coin - ' . (int)$diamondAmount),
                    'user_balance'  => DB::raw('user_balance + ' . $balanceAmount),
                ]);

            // 鑽石流水（支出）— wallet_type=4 對應鑽石/金幣類
            DB::table('user_wallet_record')->insert([
                'user_id'          => $userId,
                'wallet_type'      => 4,
                'payment_mode'     => 6,  // 6=鑽石兌換（新約定，與購買/充值區分）
                'wallet_explain'   => '鑽石兌換為餘額',
                'en_wallet_explain'=> 'Diamonds exchanged to balance',
                'wallet_money'     => -$diamondAmount,
                'created_at'       => date('Y-m-d H:i:s'),
                'updated_at'       => date('Y-m-d H:i:s')
            ]);
            // 餘額流水（收入）— wallet_type=2 對應餘額類
            DB::table('user_wallet_record')->insert([
                'user_id'          => $userId,
                'wallet_type'      => 2,
                'payment_mode'     => 6,
                'wallet_explain'   => '鑽石兌換為餘額',
                'en_wallet_explain'=> 'Diamonds exchanged to balance',
                'wallet_money'     => $balanceAmount,
                'created_at'       => date('Y-m-d H:i:s'),
                'updated_at'       => date('Y-m-d H:i:s')
            ]);

            // 金幣兌換記錄（後台「用戶管理 → 金幣兌換記錄」）
            if (\App\Models\Base::hasTableCached('diamond_coin_exchange_log')) {
                DB::table('diamond_coin_exchange_log')->insert([
                    'user_id'       => $userId,
                    'diamond_amount'=> $diamondAmount,
                    'coin_amount'   => $balanceAmount,
                    'rate'          => $rate,
                    'created_at'    => date('Y-m-d H:i:s'),
                    'updated_at'    => date('Y-m-d H:i:s')
                ]);
            }

            $newUser = User::where('user_id', $userId)->select('user_coin', 'user_balance')->first();
            DB::commit();
            return $this->success(__('api.exchange_success'), [
                'user_coin'    => (float)($newUser->user_coin ?? 0),
                'user_balance' => (float)($newUser->user_balance ?? 0),
                'rate'         => $rate,
                'exchanged'    => [
                    'diamond' => $diamondAmount,
                    'balance' => $balanceAmount,
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('鑽石兌換餘額失敗', ['user_id'=>$userId, 'amount'=>$diamondAmount, 'error'=>$e->getMessage()]);
            return $this->error(__('api.network_error'));
        }
    }


/**********************************************************END**********************************************************************/
}
