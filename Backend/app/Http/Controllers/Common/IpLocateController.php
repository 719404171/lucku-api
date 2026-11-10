<?php
/*
 * File: IpLocateController.php
 * Created Time: 2024-05-29 11:27:52 am
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-05-30 4:16:11 pm
 * Modified By: WangYuQiang && WangpeiGang (719404171@qq.com>)
 * -----
 * Copyright (c) 2024 成都睿創科技有限公司
 */


namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;

class IpLocateController
{
    /**
     * @param $data
     * @return mixed
     * 獲取IP地址
     */
    public static function IpLocateApi($ip)
    {
        // 云市场分配的密钥Id
        $secretId = 'AKIDjHTPr81Xu2Fz0Mtn2pI2vK1AqCEO16j8jl9X';
        // 云市场分配的密钥Key
        $secretKey = '3gm9sja2q8bO2TC7LIgLh9o8bqSp0akBtopfXd2R';
        $source = 'market';
        // 签名
        $datetime = gmdate('D, d M Y H:i:s T');
        $signStr = sprintf("x-date: %s\nx-source: %s", $datetime, $source);
        $sign = base64_encode(hash_hmac('sha1', $signStr, $secretKey, true));
        $auth = sprintf('hmac id="%s", algorithm="hmac-sha1", headers="x-date x-source", signature="%s"', $secretId, $sign);

        // 请求方法
        $method = 'GET';
        // 请求头
        $headers = array(
            'X-Source' => $source,
            'X-Date' => $datetime,
            'Authorization' => $auth,
        );
        // 查询参数
        $queryParams = array(
            'ip' => $ip,
        );
        // body参数（POST方法下）
        $bodyParams = array(
        );
        // url参数拼接
        $url = 'https://service-jr977f7k-1301115409.gz.apigw.tencentcs.com/release/';
        if (count($queryParams) > 0) {
            $url .= '?' . http_build_query($queryParams);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //取消證書檢查
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_map(function ($v, $k) {
            return $k . ': ' . $v;
        }, array_values($headers), array_keys($headers)));
        if (in_array($method, array('POST', 'PUT', 'PATCH'), true)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($bodyParams));
        }

        $data = curl_exec($ch);
        if (curl_errno($ch)) {
            // echo "Error: " . curl_error($ch);
            return '';
        } else {
            $data = json_decode($data, true);
            return $data;
        }
        curl_close($ch);
    }

}
