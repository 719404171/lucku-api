<?php
/*
 * File: RegistrationController.php
 * Project: NewDemo
 * Created Time: 2024-05-30 09:52:24 am
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2026-06-26 5:39:50 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2024 成都睿創科技有限公司
 */



namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\InvitationCode;
use App\Models\InvitationCodeUsageLog;
use App\Models\UserWalletRecord;
use App\Models\UserIntegralRecord;
use Illuminate\Http\Request;
use Tests\CreatesApplication;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Api\BaseController;

class RegistrationController extends Controller{

    private $Users;
    public function __construct()
    {
        $this->Users = new User();
    }

    /**
     * 同 IP 註冊頻率限制
     * 規則：1 小時內同一 IP 最多成功註冊 5 個帳號
     * 超限返回 false，調用方應拒絕註冊
     */
    private function checkIpRegisterRate(string $ip): bool
    {
        if ($ip === '' || $ip === '127.0.0.1' || $ip === '::1') {
            return true; // 本地放行
        }
        $key = 'register:ip:' . md5($ip);
        $count = (int)Cache::get($key, 0);
        if ($count >= 5) {
            return false;
        }
        return true;
    }

    private function bumpIpRegisterRate(string $ip): void
    {
        if ($ip === '' || $ip === '127.0.0.1' || $ip === '::1') {
            return;
        }
        $key = 'register:ip:' . md5($ip);
        $count = (int)Cache::get($key, 0);
        Cache::put($key, $count + 1, 3600);
    }

    /**
     * 郵箱註冊域名白名單
     * 僅允許 Apple（iCloud）/ Gmail / Hotmail / Yahoo / Outlook，含港台日常見地區域名
     */
    private const REGISTER_EMAIL_DOMAINS = [
        // Apple
        'icloud.com', 'me.com', 'mac.com',
        // Gmail
        'gmail.com',
        // Hotmail（含港台日地區域名）
        'hotmail.com', 'hotmail.com.hk', 'hotmail.com.tw', 'hotmail.co.jp',
        // Yahoo（含港台日地區域名）
        'yahoo.com', 'yahoo.com.hk', 'yahoo.com.tw', 'yahoo.co.jp',
        // Outlook（含 live.com）
        'outlook.com', 'live.com',
    ];

    /**
     * 校驗郵箱是否屬於允許註冊的域名（白名單製，域名大小寫不敏感）
     */
    private function isAllowedRegisterEmail(string $email): bool
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        $atPos = strrpos($email, '@');
        if ($atPos === false) {
            return false;
        }
        $domain = strtolower(substr($email, $atPos + 1));
        return in_array($domain, self::REGISTER_EMAIL_DOMAINS, true);
    }

    /**
     * 讀取「註冊是否需要郵箱驗證」開關
     * 1 = 開啟：必須郵箱註冊 + 郵箱驗證才能登錄
     * 0 = 關閉：手機號可直接註冊，郵箱註冊也不需要驗證
     */
    private function isEmailVerifyRequired(): bool
    {
        try {
            $val = DB::table('shop_setting')->where('shop_status', '<>', -1)->value('email_verify_required');
            return (int)($val ?? 0) === 1;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * @param Request $request
     * @return array
     * 用戶註冊
     */
    public function register(Request $request){
        // === 真人驗證：reCAPTCHA v3 優先，未啟用時降級算術驗證碼 ===
        if (\App\Services\RecaptchaService::enabled()) {
            $recaptchaToken = trim((string)$request->post('recaptcha_token', ''));
            if ($recaptchaToken === '') {
                return $this->error('請完成真人驗證');
            }
            $rc = \App\Services\RecaptchaService::verify($recaptchaToken, 'register');
            if (!$rc->pass) {
                \Illuminate\Support\Facades\Log::warning('reCAPTCHA 未通過', [
                    'score'  => $rc->score,
                    'reason' => $rc->reason,
                    'ip'     => $request->ip(),
                ]);
                return $this->error('真人驗證失敗，請重試');
            }
        } else {
            // 降級：算術驗證碼
            $captchaToken  = trim((string)$request->post('captcha_token', ''));
            $captchaAnswer = trim((string)$request->post('captcha_answer', ''));
            if ($captchaToken === '' || $captchaAnswer === '') {
                return $this->error('请先完成算术验证码');
            }
            if (!\App\Http\Controllers\Api\CaptchaController::check($captchaToken, $captchaAnswer)) {
                return $this->error('验证码错误或已过期，请重新获取');
            }
        }

        // === 同 IP 註冊頻率限制（1 小時 ≤ 5 個）===
        $ip = (string)$request->ip();
        if (!$this->checkIpRegisterRate($ip)) {
            return $this->error('當前 IP 註冊過於頻繁，請稍後再試');
        }

        if ($request->has('user_phone') && $request->has('user_password')) {
            // === 如果後台開啟了「必須郵箱驗證」，拒絕手機號註冊 ===
            if ($this->isEmailVerifyRequired()) {
                return $this->error('當前系統需通過郵箱驗證後才能註冊，請使用郵箱註冊');
            }

            $userPhone = (string)$request->post("user_phone");
            // 同一手機號的註冊請求串行化，避免「查重 → 寫入」之間被並發插入多個帳號
            $lock = null;
            try {
                $lock = Cache::lock('register:phone:' . md5($userPhone), 20);
            } catch (\Throwable $e) {
                // 快取驅動不支援鎖時降級處理
                $lock = null;
            }
            if ($lock && !$lock->get()) {
                return $this->error(__('api.register_processing'));
            }

            try {
                $UserPhone = User::where("user_phone", $userPhone)->select('user_phone')->first();
                if (!empty($UserPhone)) {
                    return $this->error( __('api.phone_exist'));
                }
                $UserEmail = null;
                if (!empty($request->post("user_email"))) {
                    $UserEmail = User::where("user_email", trim($request->post("user_email")))->select('user_email')->first();
                }
                if (!empty($UserEmail)) {
                    return $this->error( __('api.email_exist'));
                }
                DB::beginTransaction();
                try {
                    $AppName=config('services.basics.base_name');
                    $user = new User();
                    $user->user_phone = $userPhone;
                    $user->area_code = empty($request->post("area_code")) ? '852' : $request->post("area_code");
                    $user->user_password = bcrypt($request->post("user_password"));
                    // 未填郵箱時存 NULL 而非空串，避免多條空串撞上 user_email 唯一索引
                    $user->user_email = empty($request->post("user_email")) ? null : trim($request->post("user_email"));
                    $user->user_name = empty($request->post("user_name")) ?  $AppName.rand(1,3).time() : $request->post("user_name");
                    $user->user_number =time().rand(100000,999999);
                    // 二維碼改為響應返回後異步生成並上傳，避免 OSS 上傳阻塞註冊接口
                    $user->user_code = null;
                    $user->user_status = 1;
                    $user->login_type = 1;
                    $user->register_ip = $ip;
                    $user->last_login_ip = $ip;
                    $user->user_avatar = config('services.basics.user_avatar');
                    $user->created_at = date('Y-m-d H:i:s');
                    $user->updated_at = date('Y-m-d H:i:s');
                    $user->finally_at = date('Y-m-d H:i:s');
                    $res = $user->save();
                    if ($res) {
                        // 根據會員等級添加登錄獎勵
                        $this->Users->addLoginBonus($user->user_id);
                        $results['token'] = $user->createToken($user->user_id)->accessToken;
                        $this->bumpIpRegisterRate($ip); // 成功註冊才累加
                        DB::commit();
                        return $this->success(__('api.registert_success') ,  $results);
                    } else {
                        DB::rollBack();
                        return $this->error(__('api.register_fail'));
                    }
                } catch (\Exception $e) {
                    DB::rollBack();
                   return $this->error(__('api.register_fail'),$e->getMessage());
                }
            } finally {
                optional($lock)->release();
            }
        } else {
            return $this->parametersError(__('api.param_error'));
        }
    }

    /**
     * @param Request $request
     * @return array
     * 郵箱註冊
     * 同一郵箱加鎖序列化，避免用戶連點/並發寫入多個相同帳號；
     * 已註冊但未驗證的郵箱不新建帳號，改為限頻重發驗證郵件；
     * 驗證郵件在響應返回後再發，避免 SMTP 阻塞接口。
     */
    public function registerEmail(Request $request){
        // === 真人驗證：reCAPTCHA v3 優先，未啟用時降級算術驗證碼 ===
        if (\App\Services\RecaptchaService::enabled()) {
            $recaptchaToken = trim((string)$request->post('recaptcha_token', ''));
            if ($recaptchaToken === '') {
                return $this->error('請完成真人驗證');
            }
            $rc = \App\Services\RecaptchaService::verify($recaptchaToken, 'register_email');
            if (!$rc->pass) {
                \Illuminate\Support\Facades\Log::warning('reCAPTCHA 未通過', [
                    'score'  => $rc->score,
                    'reason' => $rc->reason,
                    'ip'     => $request->ip(),
                ]);
                return $this->error('真人驗證失敗，請重試');
            }
        } else {
            // 降級：算術驗證碼
            $captchaToken  = trim((string)$request->post('captcha_token', ''));
            $captchaAnswer = trim((string)$request->post('captcha_answer', ''));
            if ($captchaToken === '' || $captchaAnswer === '') {
                return $this->error('请先完成算术验证码');
            }
            if (!\App\Http\Controllers\Api\CaptchaController::check($captchaToken, $captchaAnswer)) {
                return $this->error('验证码错误或已过期，请重新获取');
            }
        }

        // === 同 IP 註冊頻率限制（1 小時 ≤ 5 個）===
        $ip = (string)$request->ip();
        if (!$this->checkIpRegisterRate($ip)) {
            return $this->error('當前 IP 註冊過於頻繁，請稍後再試');
        }

        if (!$request->has('user_email')) {
            return $this->error(__('api.email_missing'));
        } else if (!$request->has('user_password')) {
            return $this->error(__('api.password_missing'));
        }

        $userEmail = trim((string)$request->post("user_email"));
        if ($userEmail === '') {
            return $this->error(__('api.email_missing'));
        }

        // 僅允許 Apple（iCloud）/ Gmail / Hotmail / Yahoo / Outlook 郵箱註冊
        if (!$this->isAllowedRegisterEmail($userEmail)) {
            return $this->error(__('api.email_domain_not_allowed'));
        }

        // 同一郵箱的註冊請求串行化，避免「查重 → 寫入」之間被並發插入
        $lock = null;
        try {
            $lock = Cache::lock('register:email:' . md5(strtolower($userEmail)), 20);
        } catch (\Throwable $e) {
            // 快取驅動不支援鎖時降級處理，仍有 user_email 唯一索引兜底
            $lock = null;
        }
        if ($lock && !$lock->get()) {
            return $this->error(__('api.email_processing'));
        }

        try {
            $existing = User::where('user_email', $userEmail)->first();
            if (!empty($existing)) {
                // 已驗證正常或被封禁的帳號：直接提示已存在，不再重複發信
                $isBanned = !empty($existing->ban_until) || !empty($existing->ban_reason);
                if ((int)$existing->user_status !== 0 || $isBanned) {
                    return $this->error(__('api.email_exist'));
                }

                // 已註冊但未驗證：不新建帳號，同一郵箱 60 秒內只重發一次，避免郵件轟炸
                $throttleKey = 'register:email:sent:' . md5(strtolower($userEmail));
                if (Cache::has($throttleKey)) {
                    return $this->error(__('api.email_unverified_wait'));
                }
                $this->sendEmailVerifyMail($userEmail);
                Cache::put($throttleKey, 1, 60);
                return $this->error(__('api.email_unverified_resend'));
            }

            if (!empty($request->post("user_phone"))) {
                $UserPhone = User::where("user_phone", $request->post("user_phone"))
                    ->where('area_code', $request->post("area_code") ?: '852')
                    ->select('user_phone')->first();
                if (!empty($UserPhone)) {
                    return $this->error(__('api.phone_exist'));
                }
            }

            DB::beginTransaction();
            try {
                $AppName = config('services.basics.base_name');
                $user = new User();
                $user->user_email = $userEmail;
                $user->user_phone = empty($request->post("user_phone")) ? '' : $request->post("user_phone");
                $user->area_code = empty($request->post("area_code")) ? '852' : $request->post("area_code");
                $user->user_password = bcrypt($request->post("user_password"));
                $user->user_name = empty($request->post("user_name")) ? $AppName.rand(1,3).time() : $request->post("user_name");
                $user->user_number = time().rand(100000,999999);
                $user->user_code = $this->getQRCode($user->user_number);
                // === 根據開關決定是否需要驗證 ===
                // 開啟 (email_verify_required=1) → user_status=0，驗證後才變 1
                // 關閉 (email_verify_required=0) → user_status=1，直接可用
                $user->user_status = $this->isEmailVerifyRequired() ? 0 : 1;
                $user->login_type = 4;
                $user->register_ip = (string)$request->ip();
                $user->last_login_ip = (string)$request->ip();
                $user->user_avatar = config('services.basics.user_avatar');
                $user->created_at = date('Y-m-d H:i:s');
                $user->updated_at = date('Y-m-d H:i:s');
                $user->finally_at = date('Y-m-d H:i:s');
                $res = $user->save();
                if ($res) {
                    // === 只有開啟驗證時才發驗證郵件 ===
                    if ($this->isEmailVerifyRequired()) {
                        $this->sendEmailVerifyMail($userEmail);
                        Cache::put('register:email:sent:' . md5(strtolower($userEmail)), 1, 60);
                    }

                    $this->bumpIpRegisterRate($ip); // 成功註冊才累加
                    DB::commit();
                    $this->queueUserQrCode($user->user_id, $user->user_number);
                    return $this->success(__('api.registert_success'));
                } else {
                    DB::rollBack();
                    return $this->error(__('api.register_fail'));
                }
            } catch (\Throwable $e) {
                DB::rollBack();
                // 唯一索引衝突（並發寫入同一郵箱）時給出明確提示
                if (strpos($e->getMessage(), 'uniq_users_email') !== false || strpos($e->getMessage(), '1062') !== false) {
                    return $this->error(__('api.email_exist'));
                }
                return $this->error(__('api.register_fail'), $e->getMessage());
            }
        } finally {
            optional($lock)->release();
        }
    }

    /**
     * 生成驗證令牌並發送驗證郵件（實際發信在響應返回後執行，不阻塞接口）
     * @param string $userEmail
     * @return void
     */
    private function sendEmailVerifyMail($userEmail)
    {
        $plainToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $plainToken);
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $userEmail],
            ['token' => $tokenHash, 'created_at' => date('Y-m-d H:i:s')]
        );

        $baseUri = rtrim((string)config('services.basics.base_uri'), '/');
        $verifyUrl = $baseUri.'/api/verifyEmail?email='.urlencode($userEmail).'&token='.urlencode($plainToken);
        $mail = new MailController();
        $mail->MessageEmails([
            'Email'   => $userEmail,
            'Message' => '請點擊下方鏈接完成郵箱驗證',
            'Link'    => $verifyUrl,
            'Subject' => '郵箱驗證',
        ]);
    }

    /**
     * @param Request $request
     * @return array
     * 郵箱驗證
     */
    public function verifyEmail(Request $request){
        $userEmail = $request->query('email');

        if (empty($userEmail)) {
            return $this->parametersError(__('api.param_error'));
        }

        $userQuery = User::where('user_email', $userEmail)->select('user_id', 'user_status');
        if (\App\Models\Base::hasColumnCached('users', 'ban_until')) {
            $userQuery->addSelect('ban_until', 'ban_reason');
        }
        // 同一郵箱的歷史遺留多條記錄一起處理，避免只驗證了其中一條仍然登錄不上
        $emailUsers = $userQuery->get();

        $appName = (string)config('services.basics.base_name');

        if ($emailUsers->isEmpty()) {
            $html = '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>驗證失敗</title><style>body{font-family:Arial,Helvetica,sans-serif;background:#f6f7fb;margin:0;padding:0} .wrap{max-width:520px;margin:12vh auto 0;background:#fff;border-radius:12px;padding:28px 22px;box-shadow:0 6px 24px rgba(0,0,0,.08)} h1{margin:0 0 8px;font-size:22px;color:#1f2937} p{margin:0;color:#4b5563;line-height:1.6} .hint{margin-top:14px;font-size:13px;color:#6b7280}</style></head><body><div class="wrap"><h1>驗證失敗</h1><p>該郵箱未註冊，請先註冊後再驗證。</p><p class="hint">'.$appName.'</p></div></body></html>';
            return response($html, 200)->header('Content-Type: text/html; charset=UTF-8');
        }

        // 可驗證的記錄：仍未驗證且未被封禁
        $pendingIds = [];
        foreach ($emailUsers as $emailUser) {
            if ((int)$emailUser->user_status === 0
                && empty($emailUser->ban_until)
                && empty($emailUser->ban_reason)) {
                $pendingIds[] = (int)$emailUser->user_id;
            }
        }

        if (empty($pendingIds)) {
            $html = '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>郵箱已驗證</title><style>body{font-family:Arial,Helvetica,sans-serif;background:#f6f7fb;margin:0;padding:0} .wrap{max-width:520px;margin:12vh auto 0;background:#fff;border-radius:12px;padding:28px 22px;box-shadow:0 6px 24px rgba(0,0,0,.08)} h1{margin:0 0 8px;font-size:22px;color:#1f2937} p{margin:0;color:#4b5563;line-height:1.6} .hint{margin-top:14px;font-size:13px;color:#6b7280}</style></head><body><div class="wrap"><h1>郵箱已驗證</h1><p>你的郵箱已完成驗證，無需重複操作。</p><p class="hint">'.$appName.'</p></div></body></html>';
            return response($html, 200)->header('Content-Type', 'text/html; charset=UTF-8');
        }

        $token = $request->query('token');
        if (empty($token)) {
            return $this->parametersError(__('api.param_error'));
        }

        $record = DB::table('password_reset_tokens')->where('email', $userEmail)->first();
        if (empty($record) || empty($record->token) || empty($record->created_at)) {
            $html = '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>驗證失敗</title><style>body{font-family:Arial,Helvetica,sans-serif;background:#f6f7fb;margin:0;padding:0} .wrap{max-width:520px;margin:12vh auto 0;background:#fff;border-radius:12px;padding:28px 22px;box-shadow:0 6px 24px rgba(0,0,0,.08)} h1{margin:0 0 8px;font-size:22px;color:#1f2937} p{margin:0;color:#4b5563;line-height:1.6} .hint{margin-top:14px;font-size:13px;color:#6b7280}</style></head><body><div class="wrap"><h1>驗證失敗</h1><p>驗證鏈接已失效，請重新註冊。</p><p class="hint">'.$appName.'</p></div></body></html>';
            return response($html, 200)->header('Content-Type', 'text/html; charset=UTF-8');
        }

        $createdAt = strtotime((string)$record->created_at);
        if (empty($createdAt) || (time() - $createdAt) > 3600) {
            DB::table('password_reset_tokens')->where('email', $userEmail)->delete();
            $html = '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>驗證超時</title><style>body{font-family:Arial,Helvetica,sans-serif;background:#f6f7fb;margin:0;padding:0} .wrap{max-width:520px;margin:12vh auto 0;background:#fff;border-radius:12px;padding:28px 22px;box-shadow:0 6px 24px rgba(0,0,0,.08)} h1{margin:0 0 8px;font-size:22px;color:#1f2937} p{margin:0;color:#4b5563;line-height:1.6} .hint{margin-top:14px;font-size:13px;color:#6b7280}</style></head><body><div class="wrap"><h1>驗證超時</h1><p>驗證鏈接已過期（有效時間1小時），請重新註冊。</p><p class="hint">'.$appName.'</p></div></body></html>';
            return response($html, 200)->header('Content-Type', 'text/html; charset=UTF-8');
        }

        $tokenHash = hash('sha256', $token);
        if (!hash_equals((string)$record->token, $tokenHash)) {
            $html = '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>驗證失敗</title><style>body{font-family:Arial,Helvetica,sans-serif;background:#f6f7fb;margin:0;padding:0} .wrap{max-width:520px;margin:12vh auto 0;background:#fff;border-radius:12px;padding:28px 22px;box-shadow:0 6px 24px rgba(0,0,0,.08)} h1{margin:0 0 8px;font-size:22px;color:#1f2937} p{margin:0;color:#4b5563;line-height:1.6} .hint{margin-top:14px;font-size:13px;color:#6b7280}</style></head><body><div class="wrap"><h1>驗證失敗</h1><p>此驗證鏈接無效（可能已被後續郵件中的新鏈接替代），請在註冊頁重新提交一次以獲取新鏈接。</p><p class="hint">'.$appName.'</p></div></body></html>';
            return response($html, 200)->header('Content-Type', 'text/html; charset=UTF-8');
        }

        $update = User::whereIn('user_id', $pendingIds)->update([
            'user_status' => 1,
            'updated_at' => date('Y-m-d H:i:s'),
            'finally_at' => date('Y-m-d H:i:s')
        ]);
        if ($update) {
            // 有效性快取 5 分鐘，驗證通過後必須清掉才能立即登錄
            foreach ($pendingIds as $pendingId) {
                Cache::forget('user:active:'.$pendingId);
            }
            DB::table('password_reset_tokens')->where('email', $userEmail)->delete();
            $html = '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>郵箱驗證成功</title><style>body{font-family:Arial,Helvetica,sans-serif;background:#f6f7fb;margin:0;padding:0} .wrap{max-width:520px;margin:12vh auto 0;background:#fff;border-radius:12px;padding:28px 22px;box-shadow:0 6px 24px rgba(0,0,0,.08)} h1{margin:0 0 8px;font-size:22px;color:#1f2937} p{margin:0;color:#4b5563;line-height:1.6} .hint{margin-top:14px;font-size:13px;color:#6b7280}</style></head><body><div class="wrap"><h1>郵箱驗證成功</h1><p>你的郵箱已完成驗證，現在可以返回網站使用郵箱登錄。</p><p class="hint">'.$appName.'</p></div></body></html>';
            return response($html, 200)->header('Content-Type', 'text/html; charset=UTF-8');
        } else {
            return $this->error(__('api.verify_fail'));
        }
    }

    /**
     * 使用邀請碼（App 手動輸入）
     * 規則：同一個邀請碼每人只能使用一次；同一個邀請碼可被多人使用（受 max_uses 限制，0=不限）
     * @param Request $request
     * @return array
     */
    public function useInvitationCode(Request $request)
    {
        $code = trim((string)$request->post('code'));
        if ($code === '') {
            return $this->parametersError(__('api.param_error'));
        }

        $userId = (int)(auth('api')->user()->user_id ?? 0);
        if ($userId <= 0) {
            return $this->error(__('api.invitation_code_invalid'));
        }

        $invitationId = (int)InvitationCode::where('code', $code)->where('status', 1)->value('invitation_id');
        if ($invitationId <= 0) {
            return $this->error(__('api.invitation_code_invalid'));
        }

        DB::beginTransaction();
        try {
            // 鎖住邀請碼行，序列化同一個邀請碼的並發使用請求
            $invitation = InvitationCode::where('invitation_id', $invitationId)->lockForUpdate()->first();
            if (empty($invitation) || !$invitation->isAvailable()) {
                DB::rollBack();
                return $this->error(__('api.invitation_code_invalid'));
            }

            // 同一個用戶對同一個邀請碼只能使用一次
            $used = InvitationCodeUsageLog::where('invitation_id', $invitationId)
                ->where('user_id', $userId)
                ->exists();
            if ($used) {
                DB::rollBack();
                return $this->error(__('api.invitation_code_used'));
            }

            $now = date('Y-m-d H:i:s');
            $rewardCoin = (int)$invitation->reward_coin;
            $rewardDiamond = (int)($invitation->reward_diamond ?? 0);

            InvitationCode::where('invitation_id', $invitationId)->update([
                'use_count' => (int)$invitation->use_count + 1,
                'updated_at' => $now,
            ]);

            InvitationCodeUsageLog::insert([
                'invitation_id' => $invitationId,
                'code' => $invitation->code,
                'user_id' => $userId,
                'reward_coin' => $rewardCoin,
                'reward_diamond' => $rewardDiamond,
                'used_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $this->grantInvitationReward($userId, $rewardCoin, $rewardDiamond, $now);

            $balance = (float)User::where('user_id', $userId)->value('user_balance');
            $coin = (float)User::where('user_id', $userId)->value('user_coin');
            DB::commit();

            return $this->success(__('api.invitation_code_success'), [
                'reward_coin' => $rewardCoin,
                'reward_diamond' => $rewardDiamond,
                'user_balance' => $balance,
                'user_coin' => $coin,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            // 唯一索引 (invitation_id,user_id) 攔下的重複提交，按「已使用過」提示而不是網絡錯誤
            if (strpos($e->getMessage(), 'uniq_icl_code_user') !== false || strpos($e->getMessage(), '1062') !== false) {
                return $this->error(__('api.invitation_code_used'));
            }
            \Illuminate\Support\Facades\Log::error('使用邀請碼失敗: ' . $e->getMessage());
            return $this->error(__('api.network_error'));
        }
    }

    /**
     * 發放邀請碼獎勵（金幣進 user_balance，鑽石進 user_coin，並各記一條錢包流水）
     */
    private function grantInvitationReward($userId, $rewardCoin, $rewardDiamond, $now)
    {
        if ($rewardCoin > 0) {
            User::where('user_id', $userId)->increment('user_balance', $rewardCoin);
            UserWalletRecord::insert([
                'user_id' => $userId,
                'wallet_type' => 2,
                'payment_mode' => 3,
                'wallet_explain' => '邀請碼獎勵金幣',
                'en_wallet_explain' => 'Invitation code bonus coins',
                'wallet_money' => $rewardCoin,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if ($rewardDiamond > 0) {
            User::where('user_id', $userId)->increment('user_coin', $rewardDiamond);
            UserWalletRecord::insert([
                'user_id' => $userId,
                'wallet_type' => 1,
                'payment_mode' => 3,
                'wallet_explain' => '邀請碼獎勵鑽石',
                'en_wallet_explain' => 'Invitation code bonus diamonds',
                'wallet_money' => $rewardDiamond,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * @param Request $request
     * @return array
     * 驗證邀請碼是否存在且可用
     */
    public function invitationCodeVerify(Request $request)
    {
        $code = $request->post('code');
        if (empty($code)) {
            return $this->error(__('api.param_error'));
        }

        $invitation = InvitationCode::where('code', $code)->where('status', 1)->first();
        if ($invitation && $invitation->isAvailable()) {
            return $this->success(__('api.success'), [
                'reward_coin' => $invitation->reward_coin,
                'reward_diamond' => $invitation->reward_diamond ?? 0,
                'use_count' => $invitation->use_count,
                'max_uses' => $invitation->max_uses,
            ]);
        }

        return $this->error(__('api.invitation_code_invalid'));
    }


/**********************************************************END**********************************************************************/
}
