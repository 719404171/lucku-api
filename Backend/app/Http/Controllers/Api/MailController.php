<?php
/*
 * File: MailController.php
 * Project: NewDemo
 * Created Time: 2024-05-29 11:27:52 am
 * Author: WangYuQiang && WangpeiGang (brainstormhongkong@gmail.com)
 * -----
 * Last Modified: 2026-09-18
 * Modified By: MailService 統一發送
 * -----
 * Copyright (c) 2023 - 2024 成都睿創科技有限公司
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\MailService;

class MailController extends Controller{

    /**
     * 統一入口：所有內部調用都走這裡
     * 響應返回後投遞，不阻塞接口
     */
    public function MessageEmails($info){
        $appName = (string)config('services.basics.base_name', 'Lucku');
        $fromAddr = (string)config('mail.mailers.smtp.username', 'auto.generate.word@gmail.com');

        MailService::sendAfterResponse('emails.Message', ['info' => $info], [
            'to'        => $info['Email'] ?? 'brainstormhongkong@gmail.com',
            'subject'   => ($info['Subject'] ?? '系統通知') . ' - ' . date('Y-m-d H:i:s'),
            'from'      => $fromAddr,
            'from_name' => $appName,
        ]);
    }

    /**
     * 發送郵箱驗證碼（用於修改密碼）
     */
    public function sendEmailVerifyCode(Request $request){
        $email = $request->post('email');
        if (empty($email)) {
            return $this->error(__('api.param_error'));
        }

        // 檢查郵箱是否已註冊
        $user = DB::table('users')->where('user_email', $email)->first();
        if (!$user) {
            return $this->error('該郵箱未註冊');
        }

        // 檢查是否頻繁發送（60秒內只能發一次）
        $lastCode = DB::table('email_verify_codes')
            ->where('email', $email)
            ->where('used', 0)
            ->orderBy('created_at', 'desc')
            ->first();
        if ($lastCode) {
            $lastTime = strtotime($lastCode->created_at);
            if (time() - $lastTime < 60) {
                return $this->error('發送太頻繁，請60秒後再試');
            }
        }

        // 生成6位隨機驗證碼
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = date('Y-m-d H:i:s', time() + 600); // 10分鐘有效

        DB::table('email_verify_codes')->insert([
            'email'      => $email,
            'code'       => $code,
            'used'       => 0,
            'expires_at' => $expiresAt,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // 響應返回後投遞
        $appName  = (string)config('services.basics.base_name', 'Lucku');
        $fromAddr = (string)config('mail.mailers.smtp.username', 'auto.generate.word@gmail.com');
        MailService::sendAfterResponse('emails.verifyCode', [
            'info' => [
                'Email'   => $email,
                'Subject' => '密碼重置驗證碼',
                'Message' => '您的驗證碼為：',
                'Code'    => $code,
            ],
        ], [
            'to'        => $email,
            'subject'   => '密碼重置驗證碼',
            'from'      => $fromAddr,
            'from_name' => $appName,
        ]);

        return $this->success('驗證碼已發送至您的郵箱');
    }

}
