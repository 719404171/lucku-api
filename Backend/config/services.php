<?php
/*
 * File: services.php
 * Created Time: 2023-10-12 3:11:16 pm
 * Author: Wang Yu Qiang (719404171@qq.com)
 * -----
 * Last Modified: 2024-07-24 5:17:08 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright 2023 - 2023 Brainstorm Ltd, 成都睿創科技有限公司
 */


return [

    /*
    |--------------------------------------------------------------------------
    |設置 config('services.basics.base_uri');
    |--------------------------------------------------------------------------
    |
    */
    /*
    |--------------------------------------------------------------------------
    | 基礎設置 config
    |--------------------------------------------------------------------------
    |
    |
    */
/***********************************************************************************************/
    'basics' => [
        'base_uri' =>  env('APP_URL'),     //基礎地址
        'user_avatar' =>  env('user_avatar'),     //默認頭像設置
        'base_name' =>  env('APP_NAME'),     //公司名
        'base_address' =>  env('APP_ADDRESS'),     //公司地址
        'base_logo' =>  env('APP_LOGO'),     //公司logo
        'draw_enable' => env('DRAW_ENABLE', true),     //抽卡入口開關：false 時 confirmDrawOrder 直接拒絕

    ],
/***********************************************************************************************/
    /*
    |--------------------------------------------------------------------------
    | 銀豹憑證設置
    |--------------------------------------------------------------------------
    |
    */
/***********************************************************************************************/
    /*
    |--------------------------------------------------------------------------
    |config('services.pospal.app_id');
    |--------------------------------------------------------------------------
    |
    |
    */
    // 'pospal' => [
    //     'app_id' => '4955217F7F849E2A9E61B817BDE262BF',  //AppID
    //     'app_Key' => "958180478727273366",  //AppSecret
    // ],

    /*
    |--------------------------------------------------------------------------
    | 百度文心一言設置
    |--------------------------------------------------------------------------
    |
    */
/***********************************************************************************************/
    /*
    |--------------------------------------------------------------------------
    |config('services.BaiDu.baidu_ai_url');
    |--------------------------------------------------------------------------
    |
    |
    */
    'BaiDu' => [
        'baidu_ai_url' => env('baidu_ai_url'),  //api_endpoint
        'baidu_ai_key' =>env('baidu_ai_key'),  //API Key
        'baidu_ai_secret' =>env('baidu_ai_secret'),  //Secret Key
    ],

    'wonder' => [
        'app_id' => env('WONDER_APP_ID', ''),
        'private_key' => env('WONDER_PRIVATE_KEY', ''),
        'webhook_public_key' => env('WONDER_WEBHOOK_PUBLIC_KEY', ''),
        'environment' => env('WONDER_ENVIRONMENT', 'sandbox'),
        'currency' => env('WONDER_CURRENCY', 'HKD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | reCAPTCHA v3 隱形驗證（註冊防機器人）
    |--------------------------------------------------------------------------
    | 前往 https://www.google.com/recaptcha/admin 註冊取得 Key
    | v3 類型選「Invisible」，域名填自己的域名 + APP Bundle ID
    |
    | enabled  = 是否啟用（false 時跳過驗證，方便本地開發）
    | sitekey  = 前端 APP/Web 拿這個去生成 token
    | secret   = 後端驗證用，絕對不要洩漏
    | score_threshold = 分數閾值 0.0~1.0，≥ 此值視為真人，< 此值視為機器人
    |                   建議 0.5，機器人嚴重時調到 0.7
    | timeout  = 調 Google API 超時秒數
    */
    'recaptcha' => [
        'enabled'         => env('RECAPTCHA_ENABLED', false),
        'sitekey'         => env('RECAPTCHA_SITEKEY', ''),
        'secret'          => env('RECAPTCHA_SECRET', ''),
        'score_threshold' => (float)env('RECAPTCHA_SCORE_THRESHOLD', 0.5),
        'timeout'         => (int)env('RECAPTCHA_TIMEOUT', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | 阿里云 OSS 設置 config('services.aliyun_oss.bucket')
    |--------------------------------------------------------------------------
    */
    'aliyun_oss' => [
        'access_key_id'     => env('ALIYUN_OSS_ACCESS_KEY_ID', ''),
        'access_key_secret' => env('ALIYUN_OSS_ACCESS_KEY_SECRET', ''),
        'endpoint'          => env('ALIYUN_OSS_ENDPOINT', 'oss-cn-hongkong.aliyuncs.com'),
        'bucket'            => env('ALIYUN_OSS_BUCKET', 'lucku01'),
        'url'               => env('ALIYUN_OSS_URL', 'https://lucku01.oss-cn-hongkong.aliyuncs.com'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Stripe 默認配置（數據庫無配置時使用）
    |--------------------------------------------------------------------------
    */
    'stripe' => [
        'key'    => env('STRIPE_PUBLISHABLE_KEY', ''),
        'secret' => env('STRIPE_SECRET', ''),
    ],

];

