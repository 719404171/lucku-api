<?php
/*
 * File: WonderPayment.php
 * Project: NewDemo
 * Created Time: 2026-07-09 02:52:26 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2026-08-20 3:30:01 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2026 成都睿創科技有限公司
 */


namespace App\Extend;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

/**
 * Class WonderPayment
 * @package App\Extend
 * Wonder 支付相关配置,数据处理
 */
class WonderPayment extends Controller
{
    private $appId;
    private $privateKey;
    private $webhookPublicKey;
    private $apiBaseUrl;
    private $environment;

    public function __construct()
    {
        $this->appId = config('services.wonder.app_id', '');
        $this->privateKey = $this->fixKeyFormat(config('services.wonder.private_key', ''));
        $this->webhookPublicKey = $this->fixKeyFormat(config('services.wonder.webhook_public_key', ''));
        $this->environment = config('services.wonder.environment', 'sandbox');
        // 參考文檔 Environments：Sandbox = gateway-stg.wonder.today / Production = gateway.wonder.today
        $this->apiBaseUrl = $this->environment === 'live' 
            ? 'https://gateway.wonder.today' 
            : 'https://gateway-stg.wonder.today';
    }
    
    /**
     * 修复密钥格式
     * @param string $key
     * @return string
     */
    private function fixKeyFormat($key)
    {
        if (empty($key)) {
            return '';
        }
        
        // 替换 \n 为实际换行符
        $key = str_replace('\n', "\n", $key);
        
        // 确保密钥有正确的格式
        if (strpos($key, '-----BEGIN') === false && strpos($key, '-----END') === false) {
            // 如果没有包裹标记，尝试添加（假设是私钥）
            $key = "-----BEGIN PRIVATE KEY-----\n" . $key . "\n-----END PRIVATE KEY-----";
        }
        
        return $key;
    }
    
    /**
     * 检查是否配置了有效的密钥
     * @return bool
     */
    public function hasValidConfig()
    {
        return !empty($this->appId) && !empty($this->privateKey);
    }

    /**
     * 生成签名
     * @param string $httpMethod
     * @param string $httpUri
     * @param string $body
     * @param string $nonce
     * @param string $requestTime
     * @return string
     */
    private function generateSignature($httpMethod, $httpUri, $body, $nonce, $requestTime)
    {
        // 如果没有有效配置，返回空签名
        if (empty($this->appId) || empty($this->privateKey)) {
            return '';
        }
        
        // 預簽名內容：METHOD\nURI[ \nBODY]（對照官方 PHP 範例，METHOD 統一為大寫）
        $content = strtoupper($httpMethod) . "\n" . $httpUri;
        if ($body !== null && strlen(trim($body)) > 0) {
            $content .= "\n" . $body;
        }

        // Step 1: HMAC_SHA256($REQUEST_TIME, $NONCE)
        $signatureKey = hash_hmac('sha256', $requestTime, $nonce, true);
        
        // Step 2: HMAC_SHA256($ALGORITHM, $SIGNATURE_KEY)
        $signatureKey = hash_hmac('sha256', 'Wonder-RSA-SHA256', $signatureKey, true);
        
        // Step 3: HMAC_SHA256($CONTENT, $SIGNATURE_KEY)
        $plainSignature = bin2hex(hash_hmac('sha256', $content, $signatureKey, true));
        
        // Step 4: RSA_SHA256_PKCS1v15 sign and base64 encode
        $finalSignature = '';
        try {
            openssl_sign($plainSignature, $finalSignature, $this->privateKey, OPENSSL_ALGO_SHA256);
        } catch (\Exception $e) {
            Log::error('Wonder Payment Signature Error', ['error' => $e->getMessage()]);
            return '';
        }
        
        return base64_encode($finalSignature);
    }

    /**
     * 生成随机 Nonce
     * @return string
     */
    private function generateNonce()
    {
        return bin2hex(random_bytes(8));
    }

    /**
     * 生成请求时间 (UTC 格式)
     * @return string
     */
    private function generateRequestTime()
    {
        return gmdate('YmdHis');
    }

    /**
     * 生成 UUID v4
     * @return string
     */
    private function generateUuid()
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * 发送 API 请求
     * @param string $method
     * @param string $uri
     * @param array $data
     * @param array $queryParams
     * @return array
     */
    public function sendRequest($method, $uri, $data = [], $queryParams = [])
    {
        $url = $this->apiBaseUrl . $uri;
        
        // 簽名需涵蓋完整 request-target（path + query string），已實測確認
        $signingUri = $uri;
        if (!empty($queryParams)) {
            $queryString = http_build_query($queryParams);
            $url .= '?' . $queryString;
            $signingUri .= '?' . $queryString;
        }

        $body = !empty($data) ? json_encode($data) : '';
        $nonce = $this->generateNonce();
        $requestTime = $this->generateRequestTime();
        $xRequestId = $this->generateUuid();
        
        $credential = $this->appId . '/' . $requestTime . '/Wonder-RSA-SHA256';
        $signature = $this->generateSignature($method, $signingUri, $body, $nonce, $requestTime);

        $headers = [
            'Content-Type: application/json',
            'Credential: ' . $credential,
            'Signature: ' . $signature,
            'Nonce: ' . $nonce,
            'X-Request-ID: ' . $xRequestId,
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        if ($method === 'POST' && !empty($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            Log::error('Wonder Payment API Error', ['error' => $error]);
            return ['success' => false, 'error' => $error];
        }

        $result = json_decode($response, true);
        
        Log::info('Wonder Payment API Response', [
            'url' => $url,
            'http_code' => $httpCode,
            'response' => $result
        ]);

        // 文檔範例為 code=0，但閘道實際成功回 code=200；錯誤碼則為字串（如 E000005）
        // 用嚴格比對避免把 "E000005" 誤判為成功
        $apiCode = is_array($result) ? ($result['code'] ?? null) : null;
        $apiMessage = is_array($result) ? ($result['message'] ?? '') : '';

        $successCode = in_array($apiCode, [0, '0', 200, '200'], true);

        return [
            'success' => $httpCode === 200 && $successCode,
            'http_code' => $httpCode,
            'code' => $apiCode,
            'message' => $apiMessage,
            'data' => $result
        ];
    }

    /**
     * 创建订单
     * @param array $orderData
     * @param bool $withPaymentLink
     * @return array
     */
    public function createOrder($orderData, $withPaymentLink = true)
    {
        $queryParams = $withPaymentLink ? ['with_payment_link' => 'true'] : [];
        
        return $this->sendRequest(
            'POST',
            '/svc/payment/api/v1/openapi/orders',
            $orderData,
            $queryParams
        );
    }

    /**
     * 查询订单
     * @param string $orderNumber
     * @param string $referenceNumber
     * @return array
     */
    public function getOrder($orderNumber = null, $referenceNumber = null)
    {
        $uri = '/svc/payment/api/v1/openapi/orders';
        
        if ($orderNumber) {
            $uri .= '/' . $orderNumber;
        } elseif ($referenceNumber) {
            $uri .= '?reference_number=' . urlencode($referenceNumber);
        }

        return $this->sendRequest('GET', $uri);
    }

    /**
     * 验证 Webhook 签名
     * @param string $httpMethod
     * @param string $httpUri
     * @param string $body
     * @param string $nonce
     * @param string $credential
     * @param string $receivedSignature
     * @return bool
     */
    public function verifyWebhookSignature($httpMethod, $httpUri, $body, $nonce, $credential, $receivedSignature)
    {
        // 未配置 Webhook 公钥时必须拒绝，否则任何人可伪造回调（fail closed）
        if (empty($this->webhookPublicKey)) {
            Log::error('Wonder Payment Webhook: No public key configured, reject request');
            return false;
        }
        
        $parts = explode('/', $credential);
        if (count($parts) !== 3) {
            return false;
        }

        $appId = $parts[0];
        $requestTime = $parts[1];
        $algorithm = $parts[2];

        // 預簽名內容：METHOD\nURI[ \nBODY]（對照官方 PHP 範例）
        $content = strtoupper($httpMethod) . "\n" . $httpUri;
        if ($body !== null && strlen(trim($body)) > 0) {
            $content .= "\n" . $body;
        }

        // Step 1: HMAC_SHA256($REQUEST_TIME, $NONCE)
        $signatureKey = hash_hmac('sha256', $requestTime, $nonce, true);
        
        // Step 2: HMAC_SHA256($ALGORITHM, $SIGNATURE_KEY)
        $signatureKey = hash_hmac('sha256', $algorithm, $signatureKey, true);
        
        // Step 3: HMAC_SHA256($CONTENT, $SIGNATURE_KEY)
        $plainSignature = bin2hex(hash_hmac('sha256', $content, $signatureKey, true));
        
        // Step 4: Verify RSA signature
        try {
            $decodedSignature = base64_decode($receivedSignature);
            $result = openssl_verify($plainSignature, $decodedSignature, $this->webhookPublicKey, OPENSSL_ALGO_SHA256);
            return $result === 1;
        } catch (\Exception $e) {
            Log::error('Wonder Payment Webhook Verification Error', ['error' => $e->getMessage()]);
            return false;
        }
    }
}

