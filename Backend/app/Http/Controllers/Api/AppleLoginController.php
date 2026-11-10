<?php
/*
 * File: AppleLoginController.php
 * Project: NewDemo
 * Created Time: 2024-06-21 02:45:57 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-07-25 2:32:43 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2024 成都睿創科技有限公司
 */

namespace App\Http\Controllers\Api;

use App\Models\User;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

class AppleLoginController extends Controller{


    public function __construct(){
        $this->language = app('global_language') == "en" ? "en_" : "";
        $this->app_logo = config('services.basics.base_logo');//app的logo
    }

/**************************************蘋果登錄*********************************************************/
    /**
     * 蘋果登錄
     * @param Request $request
     * @return array
     */
    public function AppleLogin(Request $request){
        if(empty($request->post('identityToken'))){
            return $this->parametersError(__('api.param_error'));
        }else{
            $data = $request->post();
            $user_name= empty($request->post('fullName')) ? 'Apple00' .rand(11111,99999) : $request->post('fullName');
            $appleSignInPayload = self::getAppleSignInPayload($data['identityToken']);
            //获取信息
            $open_id=empty($appleSignInPayload['sub']) ? '' : $appleSignInPayload['sub'];
            // 蘋果僅在首次授權時返回 email，之後登錄不再下發，此處常為空。
            // 空郵箱必須寫 NULL 而不是 ''：users.user_email 有唯一索引，多個 '' 會 1062，多個 NULL 不衝突
            $user_email=empty($data['user_email']) ? null : $data['user_email'];
            $client_id=empty($data['client_id']) ? '' : $data['client_id'];
            $token_type=empty($data['token_type']) ? '' : $data['token_type'];
            //驗證通過
            if(!empty($appleSignInPayload)){
                $user = new User();
                // 郵箱為空時只用 open_id 匹配，否則會誤命中其它空郵箱帳號
                $appleUserQuery = User::where('open_id', $open_id);
                if (!empty($user_email)) {
                    $appleUserQuery->where('user_email', $user_email);
                }
                $userinfo = $appleUserQuery->first();
                if(empty($userinfo)){
                    try{
                        DB::beginTransaction();
                        $user->client_id = $client_id;
                        $user->token_type = $token_type;
                        $user->open_id = $open_id;
                        $user->area_code =  852;
                        $user->user_password = bcrypt('Apple123456');
                        $user->user_email =  $user_email;
                        $user->user_name = $user_name;
                        $user->user_number =time().rand(100000,999999);
                        // 二維碼改為響應返回後異步生成並上傳，避免 OSS 上傳阻塞登錄接口
                        $user->user_code = null;
                        $user->user_status = 1;
                        $user->login_type = 2;
                        $user->user_avatar = config('services.basics.user_avatar');
                        $user->created_at = date('Y-m-d H:i:s');
                        $user->updated_at = date('Y-m-d H:i:s');
                        $user->finally_at = date('Y-m-d H:i:s');
                        if($user->save()){
                            $results['token'] = $user->createToken($user->user_id)->accessToken;
                            DB::commit();
                            $this->queueUserQrCode($user->user_id, $user->user_number);
                            return $this->success(__('api.registert_success') ,  $results);
                        }else{
                            DB::rollBack();
                            return $this->error(__('api.register_fail'));
                        }
                    }catch (\Exception $e){
                        DB::rollBack();
                        return $this->error(__('api.register_fail'),$e->getMessage());
                    }
                }else{
                    if (Auth::attempt(['open_id' => $userinfo['open_id'] , 'password' => 'Apple123456'])) {
                        $USERS = Auth::user();
                        $token = $USERS->createToken($USERS['user_id'])->accessToken;
                        $updateInfo = User::where('user_id', $USERS['user_id'])->update(['token_type' => $token_type, 'client_id' => $client_id, 'last_login_ip' => request()->ip(), 'finally_at' => date('Y-m-d H:i:s') ]);
                        if($updateInfo){
                            //登錄次數加一
                            User::where('user_id', $USERS['user_id'])->increment('login_time');
                            // 根據會員等級添加登錄獎勵
                            (new User())->addLoginBonus($USERS['user_id']);
                            $results['token'] = $token;
                            $results['user_email'] = $USERS['user_email'];
                            $results['user_name'] = $USERS['user_name'];
                            $results['user_phone'] = $USERS['user_phone'];
                            $results['user_id'] = $USERS['user_id'];
                            $results['area_code'] = $USERS['area_code'];
                            return $this->success(__('api.login_success') , $results);
                        }else{
                            return $this->error(__('api.login_error'));
                        }
                    }else{
                        return $this->error(__('api.login_error'));
                    }
                }
            }else{
                 return $this->parametersError(__('api.param_error'));
            }
        }
    }




    /**
     * 蘋果登錄-验证identityToken
     * @param Request $request
     * @return array
     */
    public function getAppleSignInPayload($identityToken){
        // 调用苹果接口获取JWKS
        $apiResponse = file_get_contents('https://appleid.apple.com/auth/keys');
        $jwkSet = json_decode($apiResponse, true);
        if (!is_array($jwkSet)) {
            return false;
        }
        try {
            // 将JWKS转换为公钥
            $pubKeys = JWk::parseKeySet($jwkSet);
            // 使用公钥对JWT进行检验
            $payload = JWT::decode($identityToken, $pubKeys) ;
            $json= json_encode($payload);//对象转json
            $array = json_decode($json, true);//json转数组
            $this->LogInfo('蘋果登錄' , $payload , 'AppleSignInPayload' , true);//开启日志记录
            return $array;
        } catch (\Exception $e) {
             return  $e->getMessage();
             return false;
        }
    }










}
