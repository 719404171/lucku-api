<?php

namespace App\Services;

use GuzzleHttp\Client;

/**
 * 阿里雲國際/港澳台短信（SenderID 模式）
 *
 * 按阿里雲官方 SDK 文檔（Dysmsapi 2018-05-01 · SendMessageToGlobe）重寫：
 *   Action   = SendMessageToGlobe
 *   Version  = 2018-05-01
 *   Style    = RPC（HMAC-SHA1 簽名，SignatureVersion 1.0）
 *   Method   = POST，參數放在 Query
 *   Endpoint = dysmsapi.aliyuncs.com
 *
 * 國際/港澳台短信使用 SenderID 作為發送方標識，無需簽名與模板；
 * SenderID 需在控制台報備並審核通過。
 *
 * 說明：此處未引入 alibabacloud/dysmsapi-20180501 SDK 依賴。
 * 項目倉庫中沒有 composer.lock，服務器上執行 composer 安裝會重新解析全部依賴，
 * 存在升級 Laravel 等風險；本實現與 SDK 的請求契約、簽名算法完全一致。
 */
class AliyunSmsService
{
    /**
     * 發送驗證碼短信
     *
     * @param string $phone 不含國家碼的手機號（香港 8 位）
     * @param string $code  6 位驗證碼
     * @return array ['success' => bool, 'error' => string|null]
     */
    public static function sendCode(string $phone, string $code): array
    {
        $cfg = config('services.aliyun_sms', []);
        $keyId     = (string)($cfg['access_key_id'] ?? '');
        $keySecret = (string)($cfg['access_key_secret'] ?? '');
        $senderId  = (string)($cfg['sender_id'] ?? '');
        // SendMessageToGlobe 必須走國際站域名；使用國內域名 dysmsapi.aliyuncs.com 會返回 ServiceUnavailable
        $endpoint  = (string)($cfg['endpoint'] ?? '');
        if ($endpoint === '' || $endpoint === 'dysmsapi.aliyuncs.com') {
            $endpoint = 'dysmsapi.ap-southeast-1.aliyuncs.com';
        }

        if ($keyId === '' || $keySecret === '' || $senderId === '') {
            return ['success' => false, 'error' => '未配置阿里雲短信 AccessKey / SenderID'];
        }

        $message = self::renderMessage((string)($cfg['message'] ?? ''), $code);

        // RPC 公共參數 + 業務參數
        $params = [
            'AccessKeyId'      => $keyId,
            'Action'           => 'SendMessageToGlobe',
            'Format'           => 'JSON',
            'From'             => $senderId,
            'Message'          => $message,
            'SignatureMethod'  => 'HMAC-SHA1',
            'SignatureNonce'   => md5(uniqid((string)mt_rand(), true)),
            'SignatureVersion' => '1.0',
            'Timestamp'        => gmdate('Y-m-d\TH:i:s\Z'),
            'To'               => (string)($cfg['country_code'] ?? '852') . $phone,
            'Type'             => (string)($cfg['type'] ?? 'OTP'),
            'Version'          => '2018-05-01',
        ];
        if ((int)($cfg['validity_period'] ?? 0) > 0) {
            $params['ValidityPeriod'] = (int)$cfg['validity_period'];
        }

        $params['Signature'] = self::sign($params, $keySecret);

        $query = [];
        foreach ($params as $k => $v) {
            $query[] = self::percentEncode($k) . '=' . self::percentEncode((string)$v);
        }
        $url = 'https://' . $endpoint . '/?' . implode('&', $query);

        try {
            $client = new Client();
            $resp = $client->post($url, [
                'timeout'     => (int)($cfg['timeout'] ?? 10),
                'http_errors' => false,
            ]);
            $body = (string)$resp->getBody();
            $json = json_decode($body, true);

            if (!is_array($json)) {
                return ['success' => false, 'error' => '短信服務返回異常：' . mb_substr($body, 0, 200)];
            }
            if (($json['ResponseCode'] ?? '') === 'OK') {
                return ['success' => true, 'error' => null];
            }
            $err = trim((string)($json['ResponseCode'] ?? '') . ' ' . (string)($json['ResponseDescription'] ?? ''));
            if ($err === '') {
                // 簽名/權限類錯誤走 Code + Message
                $err = trim((string)($json['Code'] ?? '') . ' ' . (string)($json['Message'] ?? ''));
            }
            if ($err === '') {
                $err = '短信發送失敗';
            }
            return ['success' => false, 'error' => $err];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => '短信服務請求失敗：' . $e->getMessage()];
        }
    }

    /**
     * 短信正文：{code} 佔位符替換為驗證碼
     * @param string $template
     * @param string $code
     * @return string
     */
    private static function renderMessage(string $template, string $code): string
    {
        if (trim($template) === '') {
            $template = '您的驗證碼為{code}，5分鐘內有效，請勿洩露。';
        }
        return str_replace('{code}', $code, $template);
    }

    /**
     * RPC 簽名（HMAC-SHA1）
     */
    private static function sign(array $params, string $secret): string
    {
        ksort($params);
        $pairs = [];
        foreach ($params as $k => $v) {
            $pairs[] = self::percentEncode($k) . '=' . self::percentEncode((string)$v);
        }
        $stringToSign = 'POST&' . self::percentEncode('/') . '&' . self::percentEncode(implode('&', $pairs));
        return base64_encode(hash_hmac('sha1', $stringToSign, $secret . '&', true));
    }

    private static function percentEncode(string $str): string
    {
        return str_replace(['+', '*', '%7E'], ['%20', '%2A', '~'], urlencode($str));
    }
}
