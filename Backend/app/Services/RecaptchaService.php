<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * reCAPTCHA v3 隱形驗證服務
 *
 * 用法：
 *   $result = RecaptchaService::verify($token, $action);
 *   if ($result->pass) { /* 放行 *\/ }
 *
 * 返回結構：
 *   pass    => bool         // 是否通過（分數 ≥ 閾值）
 *   score   => float        // Google 返回的分數 0.0~1.0
 *   reason  => string       // 不通過原因：'invalid-token'|'timeout'|'action-mismatch'|'score-too-low'|'disabled'
 *   raw     => array        // Google 原始響應（調試用）
 */
class RecaptchaService
{
    private const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    /**
     * 驗證前端傳來的 reCAPTCHA token
     *
     * @param string $token  前端生成的 g-recaptcha-response
     * @param string $action 預期的 action 名稱（可選，用於防止 token 在多個場景復用）
     * @return object  { pass, score, reason, raw }
     */
    public static function verify(string $token, string $action = ''): object
    {
        // 未啟用時直接放行（本地開發 / 未配置 key）
        $enabled  = (bool)config('services.recaptcha.enabled', false);
        $secret   = (string)config('services.recaptcha.secret', '');
        $sitekey  = (string)config('services.recaptcha.sitekey', '');
        $threshold = (float)config('services.recaptcha.score_threshold', 0.5);
        $timeout  = (int)config('services.recaptcha.timeout', 5);

        if (!$enabled || $secret === '' || $sitekey === '') {
            return (object)[
                'pass'   => true,
                'score'  => 1.0,
                'reason' => 'disabled',
                'raw'    => [],
            ];
        }

        // token 基礎校驗
        if ($token === '' || strlen($token) < 20) {
            return (object)[
                'pass'   => false,
                'score'  => 0.0,
                'reason' => 'invalid-token',
                'raw'    => [],
            ];
        }

        // 同一 token 只允許驗證一次（防重放）
        $replayKey = 'recaptcha:used:' . md5($token);
        if (Cache::has($replayKey)) {
            return (object)[
                'pass'   => false,
                'score'  => 0.0,
                'reason' => 'invalid-token',
                'raw'    => [],
            ];
        }

        // 調 Google 驗證接口
        $resp = self::curlPost(self::VERIFY_URL, [
            'secret'   => $secret,
            'response' => $token,
            'remoteip' => request()->ip(),
        ], $timeout);

        if ($resp === null) {
            return (object)[
                'pass'   => false,
                'score'  => 0.0,
                'reason' => 'timeout',
                'raw'    => [],
            ];
        }

        if (empty($resp['success'])) {
            Log::warning('reCAPTCHA 驗證失敗', ['error-codes' => $resp['error-codes'] ?? []]);
            return (object)[
                'pass'   => false,
                'score'  => 0.0,
                'reason' => 'invalid-token',
                'raw'    => $resp,
            ];
        }

        // 檢查 action（如果指定了）
        $actionMismatch = false;
        if ($action !== '' && !empty($resp['action']) && $resp['action'] !== $action) {
            $actionMismatch = true;
        }

        // score：0.0 = 機器人, 1.0 = 真人
        $score = (float)($resp['score'] ?? 0.0);
        $pass  = !$actionMismatch && $score >= $threshold;

        // 驗證無論成功與否，token 都作廢（2 分鐘），防止重放
        Cache::put($replayKey, 1, 120);

        return (object)[
            'pass'   => $pass,
            'score'  => $score,
            'reason' => $pass ? 'ok' : ($actionMismatch ? 'action-mismatch' : 'score-too-low'),
            'raw'    => $resp,
        ];
    }

    /**
     * 獲取前端要用的 SITE_KEY（公開的，沒安全風險）
     */
    public static function sitekey(): string
    {
        return (string)config('services.recaptcha.sitekey', '');
    }

    /**
     * 是否啟用了 reCAPTCHA
     */
    public static function enabled(): bool
    {
        return (bool)config('services.recaptcha.enabled', false)
            && config('services.recaptcha.secret', '') !== ''
            && config('services.recaptcha.sitekey', '') !== '';
    }

    /**
     * cURL 發 POST（不用 Guzzle，零額外依賴）
     */
    private static function curlPost(string $url, array $data, int $timeout): ?array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => max(2, $timeout - 2),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $body    = curl_exec($ch);
        $errno   = curl_errno($ch);
        $errmsg  = curl_error($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false || $errno !== 0 || $httpCode !== 200) {
            Log::warning('reCAPTCHA cURL 請求失敗', [
                'errno'    => $errno,
                'errmsg'   => $errmsg,
                'httpCode' => $httpCode,
            ]);
            return null;
        }

        return json_decode($body, true) ?: [];
    }
}
