<?php

namespace App\Services;

use GuzzleHttp\Client;

/**
 * 阿里雲短信服務（SendSms，RPC 簽名 v1.0）
 * 只依賴 Guzzle，無需額外安裝 SDK
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
        $keyId        = (string)($cfg['access_key_id'] ?? '');
        $keySecret    = (string)($cfg['access_key_secret'] ?? '');
        $signName     = (string)($cfg['sign_name'] ?? '');
        $templateCode = (string)($cfg['template_code'] ?? '');

        if ($keyId === '' || $keySecret === '' || $signName === '' || $templateCode === '') {
            return ['success' => false, 'error' => '未配置阿里雲短信 AccessKey / 簽名 / 模板'];
        }

        $params = [
            'AccessKeyId'      => $keyId,
            'Action'           => 'SendSms',
            'Format'           => 'JSON',
            'PhoneNumbers'     => (string)($cfg['country_code'] ?? '852') . $phone,
            'SignName'         => $signName,
            'SignatureMethod'  => 'HMAC-SHA1',
            'SignatureNonce'   => md5(uniqid((string)mt_rand(), true)),
            'SignatureVersion' => '1.0',
            'TemplateCode'     => $templateCode,
            'TemplateParam'    => json_encode(['code' => $code], JSON_UNESCAPED_UNICODE),
            'Timestamp'        => gmdate('Y-m-d\TH:i:s\Z'),
            'Version'          => '2017-05-25',
        ];
        if (!empty($cfg['region_id'])) {
            $params['RegionId'] = (string)$cfg['region_id'];
        }

        $params['Signature'] = self::sign($params, $keySecret);

        $query = [];
        foreach ($params as $k => $v) {
            $query[] = self::percentEncode($k) . '=' . self::percentEncode((string)$v);
        }

        $endpoint = (string)($cfg['endpoint'] ?? '') ?: 'dysmsapi.aliyuncs.com';
        $url = 'https://' . $endpoint . '/?' . implode('&', $query);

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
            if (($json['Code'] ?? '') === 'OK') {
                return ['success' => true, 'error' => null];
            }
            return ['success' => false, 'error' => trim(($json['Code'] ?? '') . ' ' . ($json['Message'] ?? ''))];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => '短信服務請求失敗：' . $e->getMessage()];
        }
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
