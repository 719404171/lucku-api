<?php
/*
 * File: AdminsController.php
 * Created Time: 2024-05-29 11:27:52 am
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-05-30 4:13:42 pm
 * Modified By: WangYuQiang && WangpeiGang (719404171@qq.com>)
 * -----
 * Copyright (c) 2024 成都睿創科技有限公司
 */


namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Common\AdminTokenController;
use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\Admins;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use App\Models\UserMessage;
use App\Services\AliyunSmsService;
use App\Services\MailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use OSS\Core\OssException;
use OSS\OssClient;

class AdminsController extends Controller
{
    protected $Admins;
    private $Date;

    public function __construct()
    {
        $this->Admins = new Admins();
        $this->Date = [
            [date('Y-m-d 00:00:00', strtotime('-' . 0 . " day")), date('Y-m-d 23:59:59', strtotime('-' . 0 . " day"))],
            [date('Y-m-d 00:00:00', strtotime('-' . 1 . " day")), date('Y-m-d 23:59:59', strtotime('-' . 1 . " day"))],
            [date('Y-m-d 00:00:00', strtotime('-' . 7 . " day")), date('Y-m-d 23:59:59', strtotime('-' . 0 . " day"))],
            [date('Y-m-d 00:00:00', strtotime('-' . 30 . " day")), date('Y-m-d 23:59:59', strtotime('-' . 0 . " day"))]
        ];
    }

    /**
     * 登錄頁配置：分別返回短信 / 郵箱 / 安全問題驗證開關
     * @return array
     */
    public function getAdminLoginConfigWeb(){
        return $this->success(__('web.success'), [
            'sms_enabled'      => self::adminSwitch('admin_sms_enabled'),
            'email_enabled'    => self::adminSwitch('admin_email_enabled'),
            'question_enabled' => self::adminSwitch('admin_question_enabled'),
        ]);
    }

    /**
     * 發送後台登錄短信驗證碼（兼容舊接口：先校驗帳號密碼）
     * @param Request $request
     * @return array
     */
    public function sendAdminLoginSms(Request $request){
        $username = trim((string)$request->input('username'));
        $password = (string)$request->input('password');
        if($username === '' || $password === ''){
            return $this->parametersError();
        }
        if(!self::adminSmsEnabled()){
            return $this->error(__('web.sms_not_enabled'));
        }
        $hasAdmin = Admins::where('username',$username)->where('status',1)->first();
        if(empty($hasAdmin) || !self::checkAdminPassword($hasAdmin, $password)){
            self::writeLoginFailLog($request, $username, __('web.login_error'));
            return $this->error(__('web.login_error'));
        }
        return $this->dispatchVerifyCode($hasAdmin, 'sms', $request);
    }

    /**
     * 第二階段：按 challenge 發送驗證碼（短信 / 郵箱）
     * @param Request $request
     * @return array
     */
    public function sendAdminVerifyCode(Request $request){
        $challenge = trim((string)$request->input('challenge_token'));
        $channel = trim((string)$request->input('channel'));
        if($challenge === '' || !in_array($channel, ['sms','email'], true)){
            return $this->parametersError();
        }
        $ctx = self::findChallenge($challenge);
        if($ctx === null){
            return $this->error(__('web.verify_expired'));
        }
        if(!in_array($channel, $ctx->methods, true)){
            return $this->error(__('web.verify_channel_off'));
        }
        $hasAdmin = Admins::where('admin_id',$ctx->admin_id)->where('status',1)->first();
        if(empty($hasAdmin)){
            return $this->error(__('web.login_error'));
        }
        return $this->dispatchVerifyCode($hasAdmin, $channel, $request);
    }

    /**
     * 生成並發送驗證碼（短信 / 郵箱）
     * @param Admins $hasAdmin
     * @param string $channel
     * @param Request $request
     * @return array
     */
    private function dispatchVerifyCode($hasAdmin, $channel, Request $request){
        $smsConfig = config('services.aliyun_sms');

        if($channel === 'sms'){
            $target = trim((string)$hasAdmin->phone);
            if(!preg_match('/^[4-9]\d{7}$/', $target)){
                return $this->error(__('web.sms_phone_invalid'));
            }
        }else{
            $target = trim((string)$hasAdmin->email);
            if(!filter_var($target, FILTER_VALIDATE_EMAIL)){
                return $this->error(__('web.admin_email_not_bound'));
            }
        }

        // 重發間隔（按渠道獨立計算）
        $last = DB::table('admin_sms_code')
            ->where('admin_id',$hasAdmin->admin_id)
            ->where('channel',$channel)
            ->orderByDesc('id')
            ->first();
        if(!empty($last) && strtotime($last->created_at) > time() - (int)$smsConfig['resend_interval']){
            return $this->error(__('web.sms_too_frequent'));
        }
        // 每小時上限
        $hourCount = DB::table('admin_sms_code')
            ->where('admin_id',$hasAdmin->admin_id)
            ->where('channel',$channel)
            ->where('created_at','>=',date('Y-m-d H:i:s', time() - 3600))
            ->count();
        if($hourCount >= (int)$smsConfig['hour_limit']){
            return $this->error(__('web.sms_hour_limit'));
        }

        $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        if($channel === 'sms'){
            $send = AliyunSmsService::sendCode($target, $code);
            if(empty($send['success'])){
                return $this->error(__('web.sms_send_fail'), $send['error'] ?? '');
            }
        }else{
            try{
                $appName  = (string)config('services.basics.base_name', 'Lucku');
                $fromAddr = (string)config('mail.mailers.smtp.username', '');
                $res = MailService::send('emails.verifyCode', [
                    'info' => [
                        'Email'   => $target,
                        'Subject' => '後台登錄驗證碼',
                        'Message' => '您的後台登錄驗證碼為：',
                        'Code'    => $code,
                    ],
                ], [
                    'to'        => $target,
                    'subject'   => '後台登錄驗證碼',
                    'from'      => $fromAddr ?: config('mail.from.address'),
                    'from_name' => $appName,
                ]);
                if(empty($res['success'])){
                    return $this->error(__('web.email_send_fail'), $res['error'] ?? '');
                }
            }catch(\Throwable $e){
                return $this->error(__('web.email_send_fail'), $e->getMessage());
            }
        }

        // 發送成功才落庫，並作廢該渠道未使用的舊驗證碼
        $now = date('Y-m-d H:i:s');
        DB::table('admin_sms_code')
            ->where('admin_id',$hasAdmin->admin_id)
            ->where('channel',$channel)
            ->whereNull('used_at')
            ->delete();
        DB::table('admin_sms_code')->insert([
            'admin_id'   => $hasAdmin->admin_id,
            'phone'      => $channel === 'sms' ? $target : '',
            'channel'    => $channel,
            'target'     => $target,
            'code_hash'  => hash('sha256', $code),
            'expire_at'  => date('Y-m-d H:i:s', time() + (int)$smsConfig['code_expire']),
            'attempts'   => 0,
            'used_at'    => null,
            'send_ip'    => (string)$request->ip(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->success(__('web.sms_send_success'), [
            'channel' => $channel,
            'target'  => $channel === 'sms' ? self::maskPhone($target) : self::maskEmail($target),
            'expire'  => (int)$smsConfig['code_expire'],
        ]);
    }

    /**
     * 寫入登錄 challenge（落庫，避免依賴緩存配置）
     * @param string $challenge
     * @param array $data
     */
    private static function createChallenge($challenge, array $data){
        $now = date('Y-m-d H:i:s');
        // 順帶清理過期記錄
        DB::table('admin_login_challenge')->where('expire_at','<',$now)->delete();
        DB::table('admin_login_challenge')->insert([
            'challenge_hash' => hash('sha256',(string)$challenge),
            'admin_id'       => (int)($data['admin_id'] ?? 0),
            'username'       => (string)($data['username'] ?? ''),
            'methods'        => implode(',', (array)($data['methods'] ?? [])),
            'question_id'    => (int)($data['question_id'] ?? 0),
            'attempts'       => 0,
            'ip'             => (string)($data['ip'] ?? ''),
            'expire_at'      => date('Y-m-d H:i:s', time() + (int)($data['ttl'] ?? 600)),
            'created_at'     => $now,
            'updated_at'     => $now,
        ]);
    }

    /**
     * 讀取 challenge，不存在或已過期返回 null
     * @param string $challenge
     * @return object|null
     */
    private static function findChallenge($challenge){
        $row = DB::table('admin_login_challenge')
            ->where('challenge_hash',hash('sha256',(string)$challenge))
            ->first();
        if(empty($row)){
            return null;
        }
        if(empty($row->expire_at) || strtotime($row->expire_at) < time()){
            DB::table('admin_login_challenge')->where('id',$row->id)->delete();
            return null;
        }
        $row->admin_id    = (int)$row->admin_id;
        $row->question_id = (int)$row->question_id;
        $row->attempts    = (int)$row->attempts;
        $row->methods     = array_values(array_filter(explode(',', (string)$row->methods)));
        return $row;
    }

    /**
     * 刪除 challenge
     * @param string $challenge
     */
    private static function dropChallenge($challenge){
        DB::table('admin_login_challenge')
            ->where('challenge_hash',hash('sha256',(string)$challenge))
            ->delete();
    }

    /**
     * 手機號脫敏 91234560 -> 912****60
     */
    private static function maskPhone($phone){
        $phone = (string)$phone;
        if(strlen($phone) < 5) return $phone;
        return substr($phone, 0, 3) . '****' . substr($phone, -2);
    }

    /**
     * 郵箱脫敏 abc@x.com -> a***@x.com
     */
    private static function maskEmail($email){
        $email = (string)$email;
        if(strpos($email, '@') === false) return $email;
        list($name, $domain) = explode('@', $email, 2);
        $keep = mb_substr($name, 0, 1);
        return $keep . '***@' . $domain;
    }

    /**
     * 校驗登錄驗證碼（短信 / 郵箱）
     * 僅校驗，不標記已使用；待全部驗證通過後由調用方統一寫 used_at
     * @param int $adminId
     * @param string $channel sms|email
     * @param string $code
     * @param int|null $codeId 校驗通過時返回驗證碼記錄 id
     * @param bool|null $isMismatch 是否為驗證碼不匹配（用於失敗計數）
     * @return true|string true 表示通過，字符串為錯誤提示
     */
    private static function verifyCode($adminId, $channel, $code, &$codeId = null, &$isMismatch = null){
        $codeId = null;
        $isMismatch = false;
        $row = DB::table('admin_sms_code')
            ->where('admin_id',$adminId)
            ->where('channel',$channel)
            ->whereNull('used_at')
            ->orderByDesc('id')
            ->first();
        if(empty($row)){
            return $channel === 'email' ? __('web.email_code_required') : __('web.sms_code_required');
        }
        if(strtotime($row->expire_at) < time()){
            return $channel === 'email' ? __('web.email_code_expired') : __('web.sms_code_expired');
        }
        if((int)$row->attempts >= 5){
            return $channel === 'email' ? __('web.email_code_locked') : __('web.sms_code_locked');
        }
        if(!hash_equals($row->code_hash, hash('sha256', trim((string)$code)))){
            DB::table('admin_sms_code')->where('id',$row->id)->increment('attempts');
            $isMismatch = true;
            return $channel === 'email' ? __('web.email_code_error') : __('web.sms_code_error');
        }
        $codeId = (int)$row->id;
        return true;
    }

    /**
     * 校驗安全問題（登錄時隨機抽取一題，只需答對該題）
     * @param int $adminId
     * @param int $questionId
     * @param array $answers
     * @return true|string
     */
    private static function verifyAnswers($adminId, $questionId, array $answers, &$isMismatch = null){
        $isMismatch = false;
        $row = DB::table('admin_security_question')
            ->where('id',(int)$questionId)
            ->where('admin_id',$adminId)
            ->where('status',1)
            ->first();
        if(empty($row)){
            return __('web.admin_question_not_set');
        }
        $answer = '';
        foreach($answers as $a){
            if(isset($a['id']) && (int)$a['id'] === (int)$questionId){
                $answer = (string)($a['answer'] ?? '');
                break;
            }
        }
        $answer = self::normalizeAnswer($answer);
        if($answer === ''){
            return __('web.admin_question_required');
        }
        if(!Hash::check($answer, (string)$row->answer_hash)){
            $isMismatch = true;
            return __('web.admin_question_error');
        }
        return true;
    }

    /**
     * 答案標準化：去首尾空白、壓縮空白、轉小寫
     * @param string $answer
     * @return string
     */
    private static function normalizeAnswer($answer){
        $answer = preg_replace('/\s+/u', ' ', trim((string)$answer));
        return mb_strtolower((string)$answer);
    }

    /**
     * 讀取後台登錄驗證開關（後台設置頁控制）
     * @param string $column
     * @param bool $failClosed 讀取異常時是否拋出（登錄場景用，避免驗證被靜默關閉）
     * @return bool
     */
    private static function adminSwitch($column, $failClosed = false){
        try{
            $value = DB::table('shop_setting')
                ->where('shop_status','<>',-1)
                ->orderBy('shop_id','desc')
                ->value($column);
            // 欄位不存在或無配置記錄時 value() 返回 null，按配置異常處理
            if($value === null){
                throw new \RuntimeException('admin switch not found: ' . $column);
            }
            return (int)$value === 1;
        }catch(\Throwable $e){
            if($failClosed){
                throw $e;
            }
            return false;
        }
    }

    /**
     * 短信驗證開關（保持舊調用兼容）
     * @return bool
     */
    private static function adminSmsEnabled(){
        return self::adminSwitch('admin_sms_enabled');
    }

    /**
     * 登錄（第一階段：帳號密碼）
     * 若開啟任一二次驗證，則返回 challenge_token，進入第二階段
     * @param Request $request
     * @return array
     */
    public function AdminLoginWeb(Request $request){
        $username = trim((string)$request->input('username'));
        $password = (string)$request->input('password');
        if($username === '' || $password === ''){
            return $this->parametersError();
        }

        // 登錄失敗限流：同一帳號 10 分鐘內密碼錯誤 5 次即鎖定（不含二次驗證失敗）
        $failCount = AdminLog::where('login_admin_name',$username)
            ->where('login_admin_id',0)
            ->where('login_status',0)
            ->where('created_at','>=',date('Y-m-d H:i:s', time() - 600))
            ->count();
        if($failCount >= 5){
            return $this->error(__('web.login_locked'));
        }

        $hasAdmin = Admins::where('username',$username)->where('status',1)->first();
        if(empty($hasAdmin) || !self::checkAdminPassword($hasAdmin, $password)){
            self::writeLoginFailLog($request, $username, __('web.login_error'));
            return $this->error(__('web.login_error'));
        }

        // 舊 MD5 密碼在密碼校驗通過後即升級為 bcrypt（失敗不影響登錄）
        if(!self::isBcryptHash((string)$hasAdmin->password)){
            Admins::where('admin_id',$hasAdmin->admin_id)->update(['password'=>Hash::make($password)]);
        }

        // 收集已開啟的二次驗證方式（配置讀取失敗時拒絕登錄，避免驗證被靜默關閉）
        try{
            $methods = [];
            if(self::adminSwitch('admin_sms_enabled', true))      $methods[] = 'sms';
            if(self::adminSwitch('admin_email_enabled', true))    $methods[] = 'email';
            if(self::adminSwitch('admin_question_enabled', true)) $methods[] = 'question';
        }catch(\Throwable $e){
            Log::error('admin login switch read failed: ' . $e->getMessage());
            return $this->error(__('web.login_config_error'));
        }

        if(empty($methods)){
            return $this->issueLoginToken($hasAdmin, $password, $request);
        }

        // 前置校驗：缺少綁定則直接提示，避免進入第二階段才失敗
        if(in_array('sms',$methods,true)){
            $phone = trim((string)$hasAdmin->phone);
            if(!preg_match('/^[4-9]\d{7}$/', $phone)){
                return $this->error(__('web.sms_phone_invalid'));
            }
        }
        if(in_array('email',$methods,true)){
            $email = trim((string)$hasAdmin->email);
            if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
                return $this->error(__('web.admin_email_not_bound'));
            }
        }
        $questions = [];
        $questionId = 0;
        if(in_array('question',$methods,true)){
            $all = DB::table('admin_security_question')
                ->where('admin_id',$hasAdmin->admin_id)
                ->where('status',1)
                ->orderBy('sort')
                ->orderBy('id')
                ->select('id','question')
                ->get()
                ->toArray();
            if(empty($all)){
                return $this->error(__('web.admin_question_not_set'));
            }
            // 隨機抽取一題，登錄時只需回答該題
            $pick = $all[array_rand($all)];
            $questionId = (int)$pick->id;
            $questions = [$pick];
        }

        // 生成一次性 challenge，10 分鐘內有效
        $challenge = bin2hex(random_bytes(24));
        self::createChallenge($challenge, [
            'admin_id'    => $hasAdmin->admin_id,
            'username'    => $hasAdmin->username,
            'methods'     => $methods,
            'question_id' => $questionId,
            'ip'          => (string)$request->ip(),
            'ttl'         => 600,
        ]);

        return $this->success(__('web.success'), [
            'need_verify'     => true,
            'challenge_token' => $challenge,
            'methods'         => $methods,
            'phone_mask'      => self::maskPhone($hasAdmin->phone),
            'email_mask'      => self::maskEmail($hasAdmin->email),
            'questions'       => $questions,
            'expire'          => 600,
        ]);
    }

    /**
     * 登錄（第二階段：提交二次驗證）
     * @param Request $request
     * @return array
     */
    public function AdminVerifyLogin(Request $request){
        $challenge = trim((string)$request->input('challenge_token'));
        if($challenge === ''){
            return $this->parametersError();
        }
        $ctx = self::findChallenge($challenge);
        if($ctx === null){
            return $this->error(__('web.verify_expired'));
        }
        if($ctx->attempts >= 10){
            self::dropChallenge($challenge);
            self::writeLoginFailLog($request, (string)$ctx->username, __('web.verify_too_many'), $ctx->admin_id);
            return $this->error(__('web.verify_too_many'));
        }

        $hasAdmin = Admins::where('admin_id',$ctx->admin_id)->where('status',1)->first();
        if(empty($hasAdmin)){
            self::dropChallenge($challenge);
            return $this->error(__('web.login_error'));
        }

        // 二次驗證全局限流：同一管理員 10 分鐘最多失敗 20 次，同一 IP 最多 40 次
        $windowStart = date('Y-m-d H:i:s', time() - 600);
        $adminFails = AdminLog::where('login_admin_id',$hasAdmin->admin_id)
            ->where('login_status',0)
            ->where('created_at','>=',$windowStart)
            ->count();
        $ipFails = AdminLog::where('login_ip',(string)$request->ip())
            ->where('login_status',0)
            ->where('created_at','>=',$windowStart)
            ->count();
        if($adminFails >= 20 || $ipFails >= 40){
            self::dropChallenge($challenge);
            self::writeLoginFailLog($request, (string)$ctx->username, __('web.verify_too_many'), $hasAdmin->admin_id);
            return $this->error(__('web.verify_too_many'));
        }

        $methods = $ctx->methods;
        $usedCodeIds = [];

        if(in_array('sms',$methods,true)){
            $smsCode = trim((string)$request->input('sms_code'));
            if($smsCode === ''){
                return $this->error(__('web.sms_code_required'));
            }
            $smsCodeId = null;
            $isMismatch = false;
            $check = self::verifyCode($hasAdmin->admin_id, 'sms', $smsCode, $smsCodeId, $isMismatch);
            if($check !== true){
                if($isMismatch){
                    self::bumpChallenge($challenge);
                    self::writeLoginFailLog($request, (string)$ctx->username, $check, $hasAdmin->admin_id);
                }
                return $this->error($check);
            }
            $usedCodeIds[] = $smsCodeId;
        }

        if(in_array('email',$methods,true)){
            $emailCode = trim((string)$request->input('email_code'));
            if($emailCode === ''){
                return $this->error(__('web.email_code_required'));
            }
            $emailCodeId = null;
            $isMismatch = false;
            $check = self::verifyCode($hasAdmin->admin_id, 'email', $emailCode, $emailCodeId, $isMismatch);
            if($check !== true){
                if($isMismatch){
                    self::bumpChallenge($challenge);
                    self::writeLoginFailLog($request, (string)$ctx->username, $check, $hasAdmin->admin_id);
                }
                return $this->error($check);
            }
            $usedCodeIds[] = $emailCodeId;
        }

        if(in_array('question',$methods,true)){
            $answers = $request->input('answers');
            if(is_string($answers)){
                $answers = json_decode($answers, true);
            }
            if(!is_array($answers)){
                $answers = [];
            }
            $isMismatch = false;
            $check = self::verifyAnswers($hasAdmin->admin_id, $ctx->question_id, $answers, $isMismatch);
            if($check !== true){
                if($isMismatch){
                    self::bumpChallenge($challenge);
                    self::writeLoginFailLog($request, (string)$ctx->username, $check, $hasAdmin->admin_id);
                }
                return $this->error($check);
            }
        }

        // 全部驗證通過後才作廢已使用的驗證碼，避免部分驗證失敗時碼被白白燒掉
        $usedCodeIds = array_values(array_filter($usedCodeIds));
        if(!empty($usedCodeIds)){
            DB::table('admin_sms_code')->whereIn('id',$usedCodeIds)->whereNull('used_at')->update([
                'used_at'    => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        self::dropChallenge($challenge);
        return $this->issueLoginToken($hasAdmin, '', $request);
    }

    /**
     * challenge 失敗次數累加
     * @param string $challenge
     */
    private static function bumpChallenge($challenge){
        DB::table('admin_login_challenge')
            ->where('challenge_hash',hash('sha256',(string)$challenge))
            ->increment('attempts');
    }

    /**
     * 簽發登錄 token（密碼升級、token 落庫、寫成功日誌）
     * @param Admins $hasAdmin
     * @param string $plainPassword 舊 MD5 升級用，傳空則不升級
     * @param Request $request
     * @return array
     */
    private function issueLoginToken($hasAdmin, $plainPassword, Request $request){
        // 舊 MD5 密碼在登錄成功時自動升級為 bcrypt
        if($plainPassword !== '' && !self::isBcryptHash((string)$hasAdmin->password)){
            Admins::where('admin_id',$hasAdmin->admin_id)->update(['password'=>Hash::make($plainPassword)]);
        }

        $token = AdminTokenController::createToken($hasAdmin['admin_id'], "");
        // 數據庫只保存 token 的 sha256，並記錄服務端過期時間（默認 7 天）
        $tokenTtl = (int)config('services.admin_auth.token_ttl', 604800);
        $state = Admins::where('admin_id',$hasAdmin['admin_id'])->update([
            'token' => hash('sha256', $token),
            'token_expire_at' => date('Y-m-d H:i:s', time() + $tokenTtl),
        ]);
        $adminLog = [
            'login_admin_id' => $hasAdmin['admin_id'],
            'login_admin_name' => $hasAdmin['username'],
            'login_ip' => $request->ip(),
            'login_status' => 1,
            'login_msg' => '',
            'created_at' => date('Y-m-d H:i:s')
        ];
        // 解析登錄 IP 歸屬地（失敗不影響登錄）
        $location = self::resolveIpLocation($request->ip());
        $adminLog = array_merge($adminLog, $location);
        $addAdminLog = AdminLog::insert($adminLog);
        if($state && $addAdminLog){
            return $this->success(__('web.success'),['token'=>$token]);
        }
        return $this->error();
    }

    /**
     * 是否為 bcrypt/argon 哈希
     * @param string $stored
     * @return bool
     */
    private static function isBcryptHash($stored){
        return strpos($stored, '$2y$') === 0 || strpos($stored, '$2a$') === 0 || strpos($stored, '$argon') === 0;
    }

    /**
     * 校驗管理員密碼（兼容舊 MD5 與新 bcrypt）
     * @param Admins $admin
     * @param string $plain
     * @return bool
     */
    private static function checkAdminPassword($admin, $plain){
        $stored = (string)$admin->password;
        if(self::isBcryptHash($stored)){
            return Hash::check($plain, $stored);
        }
        return hash_equals($stored, md5($plain));
    }

    /**
     * 記錄登錄失敗（用於限流與審計）
     * @param Request $request
     * @param string $username
     * @param string $msg
     */
    private static function writeLoginFailLog(Request $request, $username, $msg, $adminId = 0){
        try {
            DB::table('admin_log')->insert([
                'login_admin_id'   => (int)$adminId,
                'login_admin_name' => mb_substr((string)$username, 0, 250),
                'login_ip'         => (string)$request->ip(),
                'login_status'     => 0,
                'login_msg'        => mb_substr((string)$msg, 0, 250),
                'created_at'       => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // 日誌寫入失敗不影響登錄流程
        }
    }

    /**
     * 获取管理员权限及菜单
     * @param Request $request
     * @return array
     */
    public function getAdminMenuWeb(Request $request){
        $token = $request->header('TOKEN');
        $language = self::getLanguageWeb();
        $data = $this->Admins->getAdminMenuWeb($token,$language);
        return $this->success(__('web.success'),$data);
    }

    /**
     * 获取管理员信息
     * @param Request $request
     * @return array
     */
    public function getAdminInfoWeb(Request $request){
        $token = $request->header('TOKEN');
        $data = Admins::where('token',hash('sha256',$token))
            ->select('admin_id','username','avatar','email','phone','status')
            ->first();
        return $this->success(__('web.success'),$data);
    }

    /**
     * 获取管理員列表
     * @param Request $request
     * @return array
     */
    public function getAdminListWeb(Request $request){
        $data = $request->post();
        $token = $request->header('TOKEN');
        $language = self::getLanguageWeb();
        $datas = $this->Admins->getAdminListWeb($data,$token,$language);
        return $this->success(__('web.success'),$datas);
    }

    /**
     * 新增管理員
     * @param Request $request
     * @return array
     */
    public function addAdminWeb(Request $request){
        $adminInfo = $request->except('role_id');
        $role_id = $request->post('role_id');
        $data = $this->Admins->addAdminWeb($adminInfo,$role_id);
        return $data;
    }

    /**
     * 編輯管理員
     * @param Request $request
     * @return array
     */
    public function editAdminWeb(Request $request){
        $adminInfo = $request->except('role_id');
        $role_id = $request->post('role_id');
        $data = $this->Admins->editAdminWeb($adminInfo,$role_id);
        return $data;
    }

    /**
     * 解析 IP 歸屬地（使用 ip-api.com，免費無需 key）
     * 失敗時返回空值，不影響登錄流程
     * @param string $ip
     * @return array
     */
    private static function resolveIpLocation($ip){
        $result = [
            'login_nation' => '',
            'login_region' => '',
            'login_city' => '',
            'login_lng' => '',
            'login_lat' => '',
        ];
        try {
            // 內網/本機 IP 不解析
            if(empty($ip) || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)){
                return $result;
            }
            $ctx = stream_context_create(['http' => ['timeout' => 3]]);
            $json = @file_get_contents("http://ip-api.com/json/{$ip}?lang=zh-CN&fields=status,country,regionName,city,lat,lon", false, $ctx);
            if($json){
                $data = json_decode($json, true);
                if(!empty($data) && ($data['status'] ?? '') === 'success'){
                    $result['login_nation'] = $data['country'] ?? '';
                    $result['login_region'] = $data['regionName'] ?? '';
                    $result['login_city'] = $data['city'] ?? '';
                    $result['login_lng'] = isset($data['lon']) ? (string)$data['lon'] : '';
                    $result['login_lat'] = isset($data['lat']) ? (string)$data['lat'] : '';
                }
            }
        } catch (\Exception $e) {
            // 忽略，歸屬地解析失敗不影響登錄
        }
        return $result;
    }

    /**
     * 修改當前登錄管理員密碼
     * @param Request $request
     * @return array
     */
    public function editAdminPasswordWeb(Request $request){
        $oldPassword = $request->post('old_password');
        $newPassword = $request->post('new_password');
        if(empty($oldPassword) || empty($newPassword)){
            return $this->parametersError();
        }
        if(strlen($newPassword) < 6){
            return $this->error(__('web.password_too_short'));
        }
        $token = $request->header('TOKEN');
        $admin = Admins::where('token',hash('sha256',$token))->first();
        if(empty($admin)){
            return $this->error(__('web.login_error'));
        }
        if(!self::checkAdminPassword($admin, $oldPassword)){
            return $this->error(__('web.old_password_error'));
        }
        // 升級為 bcrypt，並強制當前令牌失效（需要重新登錄）
        $state = Admins::where('admin_id',$admin->admin_id)->update([
            'password' => Hash::make($newPassword),
            'token' => '',
            'token_expire_at' => null,
        ]);
        Cache::forget('admin:token:v2:' . md5((string)$token));
        if($state){
            return $this->success(__('web.edit_success'));
        }else{
            return $this->error(__('web.edit_fail'));
        }
    }

    /**
     * 獲取當前登錄管理員的個人資料（含安全問題）
     * @param Request $request
     * @return array
     */
    public function getAdminProfileWeb(Request $request){
        $token = $request->header('TOKEN');
        $admin = Admins::where('token',hash('sha256',$token))->first();
        if(empty($admin)){
            return $this->error(__('web.login_error'));
        }
        $questions = [];
        try{
            $questions = DB::table('admin_security_question')
                ->where('admin_id',$admin->admin_id)
                ->where('status',1)
                ->orderBy('sort')
                ->orderBy('id')
                ->select('id','question','sort')
                ->get();
        }catch(\Throwable $e){
            $questions = [];
        }
        return $this->success(__('web.success'), [
            'admin_id'         => $admin->admin_id,
            'username'         => $admin->username,
            'avatar'           => (string)$admin->avatar,
            'email'            => (string)$admin->email,
            'phone'            => (string)$admin->phone,
            'questions'        => $questions,
            'sms_enabled'      => self::adminSwitch('admin_sms_enabled'),
            'email_enabled'    => self::adminSwitch('admin_email_enabled'),
            'question_enabled' => self::adminSwitch('admin_question_enabled'),
        ]);
    }

    /**
     * 修改當前管理員頭像
     * @param Request $request
     * @return array
     */
    public function editAdminAvatarWeb(Request $request){
        $token = $request->header('TOKEN');
        $admin = Admins::where('token',hash('sha256',$token))->first();
        if(empty($admin)){
            return $this->error(__('web.login_error'));
        }
        $avatar = trim((string)$request->input('avatar',''));
        if($avatar === '' || mb_strlen($avatar) > 500 || !preg_match('#^https?://#i', $avatar)){
            return $this->parametersError();
        }
        $state = Admins::where('admin_id',$admin->admin_id)->update(['avatar'=>$avatar]);
        if($state === false){
            return $this->error(__('web.edit_fail'));
        }
        return $this->success(__('web.edit_success'), ['avatar'=>$avatar]);
    }

    /**
     * 修改當前管理員綁定的手機號 / 郵箱（需當前密碼確認）
     * @param Request $request
     * @return array
     */
    public function editAdminProfileWeb(Request $request){
        $token = $request->header('TOKEN');
        $admin = Admins::where('token',hash('sha256',$token))->first();
        if(empty($admin)){
            return $this->error(__('web.login_error'));
        }
        $email    = trim((string)$request->input('email',''));
        $phone    = trim((string)$request->input('phone',''));
        $password = (string)$request->input('password','');
        if($email === '' && $phone === ''){
            return $this->parametersError();
        }
        if($password === ''){
            return $this->error(__('web.old_password_error'));
        }
        if(!self::checkAdminPassword($admin, $password)){
            return $this->error(__('web.old_password_error'));
        }
        if($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)){
            return $this->error(__('web.email_format_error'));
        }
        if($phone !== '' && !preg_match('/^[4-9]\d{7}$/', $phone)){
            return $this->error(__('web.sms_phone_invalid'));
        }
        $update = [];
        if($email !== '') $update['email'] = $email;
        if($phone !== '') $update['phone'] = $phone;
        $state = Admins::where('admin_id',$admin->admin_id)->update($update);
        if($state === false){
            return $this->error(__('web.edit_fail'));
        }
        return $this->success(__('web.edit_success'));
    }

    /**
     * 保存當前管理員的安全問題（全量覆蓋，可多個）
     * @param Request $request
     * @return array
     */
    public function saveAdminQuestionWeb(Request $request){
        $token = $request->header('TOKEN');
        $admin = Admins::where('token',hash('sha256',$token))->first();
        if(empty($admin)){
            return $this->error(__('web.login_error'));
        }
        $password = (string)$request->input('password','');
        if($password === '' || !self::checkAdminPassword($admin, $password)){
            return $this->error(__('web.old_password_error'));
        }
        $questions = $request->input('questions');
        if(is_string($questions)){
            $questions = json_decode($questions, true);
        }
        if(!is_array($questions)){
            return $this->parametersError();
        }
        if(count($questions) > 10){
            return $this->error(__('web.admin_question_limit'));
        }

        $rows = [];
        $now  = date('Y-m-d H:i:s');
        foreach(array_values($questions) as $i => $q){
            $text   = trim((string)($q['question'] ?? ''));
            $answer = trim((string)($q['answer'] ?? ''));
            if($text === ''){
                continue;
            }
            if(mb_strlen($text) > 255){
                return $this->error(__('web.admin_question_too_long'));
            }
            if($answer === ''){
                return $this->error(__('web.admin_question_answer_required'));
            }
            $rows[] = [
                'admin_id'    => $admin->admin_id,
                'question'    => $text,
                'answer_hash' => Hash::make(self::normalizeAnswer($answer)),
                'sort'        => $i,
                'status'      => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ];
        }
        if(empty($rows)){
            return $this->error(__('web.admin_question_required'));
        }

        DB::beginTransaction();
        try{
            DB::table('admin_security_question')->where('admin_id',$admin->admin_id)->delete();
            DB::table('admin_security_question')->insert($rows);
            DB::commit();
        }catch(\Throwable $e){
            DB::rollBack();
            return $this->error(__('web.edit_fail'), $e->getMessage());
        }
        return $this->success(__('web.edit_success'));
    }

    /**
     * 刪除管理員
     * @param Request $request
     * @return array
     */
    public function deleteAdminWeb(Request $request){
        $adminID = $request->post('adminID');
        $data = $this->Admins->deleteAdminWeb($adminID);
        return $data;
    }

    /**
     * 上傳圖片
     * @param Request $request
     * @return array
     */
    public function uploadImageWeb(Request $request){
        $accessKeyId = config('services.aliyun_oss.access_key_id');
        $accessKeySecret = config('services.aliyun_oss.access_key_secret');
        // Endpoint以杭州为例，其它Region请按实际情况填写。
        $endpoint = config('services.aliyun_oss.endpoint');
        // 存储空间名称
        $bucket = config('services.aliyun_oss.bucket');
        // 文件名称
        $filePath = $request->file("file");
        $object = date("Y-m-d") . "/" . time() .rand(111111,999999). "." . $filePath->getClientOriginalExtension();
        try {
            $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
            $ossClient->uploadFile($bucket, $object, $filePath);
            $results['file_path']=config('services.aliyun_oss.url') . '/' . $object;
            return $this->success( __('api.picture_success'), $results);
        } catch (OssException $e) {
            return $this->Error(__('api.picture_error'), $e);
        }
    }

    /**
     * 获取首頁數據
     * @return mixed
     */
    public function getHomeDataWeb(){
        $data['user'] = $this->getUserNumber();
        $data['user_message'] = $this->getUserMessage();
        $data['sales_volume'] = $this->getSalesVolume();
        $data['order'] = $this->getOrderAnalysis();
        return $data;
    }

    /**
     * 獲取用戶數據
     * @return mixed
     */
    public function getUserNumber(){
        $data['count'] = User::where('user_status', '<>', -1)->where('user_id','<>',1)->count();

        $dataInfo = [];
        for ($i = 0; $i < count($this->Date); $i++) {
            $count = User::whereBetween('created_at', $this->Date[$i])->where('user_status', '<>', -1)->where('user_id','<>',1)->count();
            array_push($dataInfo, $count);
        }
        array_push($dataInfo, User::where('user_status', '<>', -1)->where('user_id','<>',1)->count());
        $data['data'] = $dataInfo;
        return $data;
    }

    /**
     * 獲取用戶留言數據
     * @return mixed
     */
    public function getUserMessage(){
        $data['count'] = UserMessage::join('users','users.user_id','=','user_message.user_id')->where('users.user_status','<>',-1)->where('user_message.message_status', '<>', -1)->count();

        $dataInfo = [];
        for ($i = 0; $i < count($this->Date); $i++) {
            $count = UserMessage::join('users','users.user_id','=','user_message.user_id')->where('users.user_status','<>',-1)->whereBetween('user_message.created_at', $this->Date[$i])->where('message_status', '<>', -1)->count();
            array_push($dataInfo, $count);
        }
        array_push($dataInfo, UserMessage::join('users','users.user_id','=','user_message.user_id')->where('users.user_status','<>',-1)->where('user_message.message_status', '<>', -1)->count());
        $data['data'] = $dataInfo;
        return $data;
    }

    /**
     * 獲取銷售額數據
     * @return mixed
     */
    public function getSalesVolume(){
        $data['count'] = Order::join('users','users.user_id','=','order.user_id')->where('users.user_status','<>',-1)->where('payment_status', '>=', 3)->where('order_status','<>',-1)->sum('order_money');

        $dataInfo = [];
        for ($i = 0; $i < count($this->Date); $i++) {
            $count = Order::join('users','users.user_id','=','order.user_id')->where('users.user_status','<>',-1)->whereBetween('payment_at', $this->Date[$i])->where('payment_status', '>=', 3)->where('order_status','<>',-1)->sum('order_money');
            array_push($dataInfo, $count);
        }
        array_push($dataInfo, Order::join('users','users.user_id','=','order.user_id')->where('users.user_status','<>',-1)->where('payment_status', '>=', 3)->where('order_status','<>',-1)->sum('order_money'));
        $data['data'] = $dataInfo;
        return $data;
    }

    /**
     * 獲取訂單數據
     * @return mixed
     */
    public function getOrderAnalysis(){
        $data['count'] = Order::join('users','users.user_id','=','order.user_id')->where('users.user_status','<>',-1)->where('payment_status', '>=', 3)->where('order_status','<>',-1)->count();

        $dataInfo = [];
        for ($i = 0; $i < count($this->Date); $i++) {
            $count = Order::join('users','users.user_id','=','order.user_id')->where('users.user_status','<>',-1)->whereBetween('payment_at', $this->Date[$i])->where('payment_status', '>=', 3)->where('order_status','<>',-1)->count();
            array_push($dataInfo, $count);
        }
        array_push($dataInfo, Order::join('users','users.user_id','=','order.user_id')->where('users.user_status','<>',-1)->where('payment_status', '>=', 3)->where('order_status','<>',-1)->count());
        $data['data'] = $dataInfo;
        return $data;
    }

}
