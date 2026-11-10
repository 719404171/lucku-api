<?php
/*
 * File: AppleLoginController.php
 * Project: NewDemo
 * Created Time: 2024-06-21 02:45:57 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2026-09-10 3:44:22 pm
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

class GoogleLoginController extends Controller{



    public function __construct(){
        $this->language = app('global_language') == "en" ? "en_" : "";
         
    }

/**************************************谷歌登錄*********************************************************/
    /**
     * 谷歌登錄
     * @param Request $request
     * @return array
     */
    public function GoogleLogin(Request $request){
        $data = $request->post();
        if(empty( $request->post('credential') )  ){
            return $this->parametersError(__('api.param_error'));
        }else{
            $data = $request->post();
            $client_id=empty($data['client_id']) ? '' : $data['client_id'];
            $token_type=empty($data['token_type']) ? '' : $data['token_type'];
            
            $GoogleignInPayload = self::getGoogleignInPayload($data['credential']);
            //获取信息
            $open_id=empty($GoogleignInPayload['sub']) ? '' : $GoogleignInPayload['sub'];
            // 空郵箱必須寫 NULL 而不是 ''：users.user_email 有唯一索引，多個 '' 會 1062，多個 NULL 不衝突
            $user_email=empty($GoogleignInPayload['email']) ? null : $GoogleignInPayload['email'];
            $user_name=empty($GoogleignInPayload['name']) ? '' : $GoogleignInPayload['name'];
            $user_avatar=empty($GoogleignInPayload['picture']) ? '' : $GoogleignInPayload['picture'];
            //驗證通過
            if(!empty($GoogleignInPayload)){
                $user = new User();
                // 郵箱為空時不按郵箱查（空串會誤命中其它空郵箱帳號，且會撞唯一索引）
                $userinfo = empty($user_email) ? null : User::where('user_email', $user_email)->first();
                if(empty($userinfo)){
                    try{
                        DB::beginTransaction();
                        $user->client_id = $client_id;
                        $user->token_type = $token_type;
                        $user->open_id = $open_id;
                        $user->area_code = 852;
                        $user->user_password = bcrypt('Google123456');
                        $user->user_email =  $user_email;
                        $user->user_name = $user_name;
                        $user->user_number =time().rand(100000,999999);
                        // 二維碼改為響應返回後異步生成並上傳，避免 OSS 上傳阻塞登錄接口
                        $user->user_code = null;
                        $user->user_status = 1;
                        $user->login_type = 3;
                        $user->user_avatar = $user_avatar;
                        $user->created_at = date('Y-m-d H:i:s');
                        $user->updated_at = date('Y-m-d H:i:s');
                        $user->finally_at = date('Y-m-d H:i:s');
                        if($user->save()){
                            $results['token'] = $user->createToken($user->user_id)->accessToken;
                            $results['user_email'] = $user_email ?? '';
                            $results['user_name'] = $user_name;
                            $results['user_id'] = $user->user_id;
                            DB::commit();
                            $this->queueUserQrCode($user->user_id, $user->user_number);
                            return $this->success(__('api.registert_success') ,$results);
                        }else{
                            DB::rollBack();
                            return $this->error(__('api.register_fail'));
                        }
                    }catch (\Exception $e){
                        DB::rollBack();
                        return $this->error(__('api.register_fail'),$e->getMessage());
                    }
                }else{
                    // 更新用戶信息
                    User::where('user_id', $userinfo['user_id'])->update([
                        'open_id' => $open_id,
                        'token_type' => $token_type,
                        'client_id' => $client_id,
                        'user_avatar' => $user_avatar ?: $userinfo['user_avatar'],
                        'last_login_ip' => request()->ip(),
                        'finally_at' => date('Y-m-d H:i:s')
                    ]);
                    // 登錄次數加一
                    User::where('user_id', $userinfo['user_id'])->increment('login_time');
                    // 根據會員等級添加登錄獎勵
                    (new User())->addLoginBonus($userinfo['user_id']);
                    
                    $token = $userinfo->createToken($userinfo->user_id)->accessToken;
                    $results['token'] = $token;
                    $results['user_email'] = $userinfo['user_email'];
                    $results['user_name'] = $userinfo['user_name'];
                    $results['user_phone'] = $userinfo['user_phone'];
                    $results['user_id'] = $userinfo['user_id'];
                    $results['area_code'] = $userinfo['area_code'];
                    return $this->success(__('api.login_success') , $results);
                }
            }else{
                 return $this->parametersError(__('api.param_error'));
            }
        }
    }




    /**
     * 谷歌登錄-验证identityToken
     * @param Request $request
     * @return array
     */
    public function getGoogleignInPayload($identityToken){
        // 调用谷歌接口获取JWKS
        $apiResponse = file_get_contents('https://www.googleapis.com/oauth2/v3/certs');
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
            $this->LogInfo('谷歌登錄' , $array , 'GoogleignInPayload' , true);//开启日志记录
            return $array;
        } catch (\Exception $e) {
             return  $e->getMessage();
             return false;
        }
    }







    


}