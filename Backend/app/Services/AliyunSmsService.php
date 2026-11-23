<?php

namespace App\Services;

use GuzzleHttp\Client;

/**
 * 阿里雲國際/港澳台短信（SenderID 模式）
 *
 * 阿里雲已將國際/港澳台短信升級為 SenderID 模式：
 * 不需要簽名與模板，使用 SendMessageToGlobe 接口，SenderID 報備通過後即可發送。
 *
 * 接口固定參數（阿里雲文檔要求，勿修改）：
 *   domain  = dysmsapi.aliyuncs.com
 *   Version = 2017-05-25
 *   RegionId= cn-hangzhou
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

        if ($keyId === '' || $keySecret === '' || $senderId === '') {
            return ['success' => false, 'error' => '未配置阿里雲短信 AccessKey / SenderID'];
        }

        $message = self::renderMessage((string)($cfg['message'] ?? ''), $code);

        $params = [
            'AccessKeyId'      => $keyId,
            'Action'           => 'SendMessageToGlobe',
            'Format'           => 'JSON',
            'From'             => $senderId,
            'Message'          => $message,
            'RegionId'         => 'cn-hangzhou',
            'SignatureMethod'  => 'HMAC-SHA1',
            'SignatureNonce'   => md5(uniqid((string)mt_rand(), true)),
            'SignatureVersion' => '1.0',
            'Timestamp'        => gmdate('Y-m-d\TH:i:s\Z'),
            'To'               => (string)($cfg['country_code'] ?? '852') . $phone,
            'Type'             => (string)($cfg['type'] ?? 'OTP'),
            'Version'          => '2017-05-25',
        ];

        $params['Signature'] = self::sign($params, $keySecret);

        $query = [];
        foreach ($params as $k => $v) {
            $query[] = self::percentEncode($k) . '=' . self::percentEncode((string)$v);
        }

        $url = 'https://dysmsapi.aliyuncs.com/?' . implode('&', $query);

        try {
            $client = new Client();
            $resp = $client->get($url, [
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
            $err = trim(($json['ResponseCode'] ?? '') . ' ' . ($json['ResponseDescription'] ?? '') . ' ' . ($json['Message'] ?? ''));
            return ['success' => false, 'error' => $err !== '' ? $err : '短信發送失敗'];
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
        $stringToSign = 'GET&' . self::percentEncode('/') . '&' . self::percentEncode(implode('&', $pairs));
        return base64_encode(hash_hmac('sha1', $stringToSign, $secret . '&', true));
    }

    private static function percentEncode(string $str): string
    {
        return str_replace(['+', '*', '%7E'], ['%20', '%2A', '~'], urlencode($str));
    }
}
