<?php
/*
 * File: AdminTokenController.php
 * Created Time: 2024-05-29 11:27:52 am
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-05-30 4:15:27 pm
 * Modified By: WangYuQiang && WangpeiGang (719404171@qq.com>)
 * -----
 * Copyright (c) 2024 成都睿創科技有限公司
 */


namespace App\Http\Controllers\Common;


use App\Http\Controllers\Controller;

class AdminTokenController extends Controller
{
    /**
     * header
     * @var array
     */
    private static $header = [
        "type" => "token",
        "alg"  => "HS256"
    ];

    /**
     * create payload
     * @param $memberId
     * @param $permission
     * @return array
     */
    private static function payload($memberId, $permission)
    {
        return [
            "iss"       => "http://api.creatshare.com",
            "iat"       => $_SERVER['REQUEST_TIME'],
            "exp"       => $_SERVER['REQUEST_TIME'] + 86400, //一天時限
            "GivenName" => "CreatShare",
            "memberId"  => $memberId,
            "permission"=> $permission
        ];
    }

    /**
     * encode data
     * @param $data
     * @return string
     */
    private static function encode($data)
    {
        return base64_encode(json_encode($data));
    }

    /**
     * generate a signature
     * @param $header
     * @param $payload
     * @param string $secret
     * @return string
     */
    private static function signature($header, $payload, $secret = 'secret')
    {
        return hash_hmac('sha256', $header.$payload, $secret);
    }

    /**
     * generate a token
     * @param $memberId
     * @param $permission
     * @return string
     */
    public static function createToken($memberId, $permission)
    {
        $header = self::encode(self::$header);
        $payload = self::encode(self::payload($memberId, $permission));
        $signature = self::signature($header, $payload);

        return $header . '.' .$payload . '.' . $signature;
    }

    /**
     * check a token
     * @param $jwt
     * @param string $key
     * @return array|string
     */
    public static function checkToken($jwt, $key = 'secret')
    {
        $token = explode('.', $jwt);
        if (count($token) != 3)
            return 'token invalid';

        list($header64, $payload64, $sign) = $token;
        if (self::signature($header64 , $payload64) !== $sign)
            return 'token invalid';
        $header = json_decode(base64_decode($header64), JSON_OBJECT_AS_ARRAY);
        $payload = json_decode(base64_decode($payload64), JSON_OBJECT_AS_ARRAY);

        if ($header['type'] != 'token' || $header['alg'] != 'HS256')
            return 'token invalid';
        if ($payload['iss'] != 'http://api.creatshare.com' || $payload['GivenName'] != 'CreatShare')
            return 'token invalid';
        return [
            'memberId' => $payload['memberId'],
            'permission' =>$payload['permission']
        ];
    }

    /**
     * get a token
     * @return null
     */
    public static function getToken()
    {
        $token = null;
        if (isset($_SERVER['TOKEN']))
            $token = $_SERVER['TOKEN'];
        return $token;
    }

}
