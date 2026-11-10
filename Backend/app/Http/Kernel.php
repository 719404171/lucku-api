<?php
/*
 * File: Kernel.php
 * Created Time: 2023-10-12 3:11:16 pm
 * Author: Wang Yu Qiang (719404171@qq.com)
 * -----
 * Last Modified: 2024-08-02 10:04:02 am
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright 2023 - 2023 Brainstorm Ltd, 成都睿創科技有限公司
 */
namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array<int, class-string|string>
     */
    protected $middleware = [
        \App\Http\Middleware\TrustProxies::class,
        \Illuminate\Http\Middleware\HandleCors::class,
        \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        \App\Http\Middleware\EnableCrossRequestMiddleware::class,//解決跨域問題
        \App\Http\Middleware\Language::class,//多語言
        \App\Http\Middleware\ShopGlobal::class,//全局店鋪設置
        \App\Http\Middleware\PaypalGlobal::class,//全局Paypal設置
        \App\Http\Middleware\GeTuiGlobal::class,//全局Getui全局變量
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array<string, array<int, class-string|string>>
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class
        ],

        'api' => [
            \App\Http\Middleware\MaintenanceNoticeHeader::class,//APP端響應頭附帶維護狀態 X-Maintenance-Enabled
            \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\LogUserRequest::class,//記錄已登錄用戶請求的IP用於攻擊溯源
            
        ],
    ];

    /**
     * The application's middleware aliases.
     *
     * Aliases may be used to conveniently assign middleware to routes and groups.
     *
     * @var array<string, class-string|string>
     */
    protected $routeMiddleware = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'signed' => \App\Http\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        'RefreshToken'=>\App\Http\Middleware\RefreshToken::class,   //检测后台管理系统是否登录
        'checkLogin' =>\App\Http\Middleware\CheckLogin::class,
        'CheckToken'=>\App\Http\Middleware\CheckToken::class,
        'prevent.duplicate'=>\App\Http\Middleware\PreventDuplicateRequest::class   //App防重複請求（Redis原子鎖）
    ];
}


