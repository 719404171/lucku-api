<?php
/*
 * File: Handler.php
 * Created Time: 2023-10-12 3:11:16 pm
 * Author: Wang Yu Qiang (719404171@qq.com)
 * -----
 * Last Modified: 2026-09-10 9:53:43 am
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright 2023 - 2023 Brainstorm Ltd, 成都睿創科技有限公司
 */
namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use League\OAuth2\Server\Exception\OAuthServerException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'user_password',
        'password_confirmation',
    ];

    /**
     * 令牌無效/未授權（Passport access_denied，code 9）屬預期情況，不寫 ERROR 日誌
     */
    public function report(Throwable $e)
    {
        if ($e instanceof OAuthServerException && $e->getCode() === 9) {
            return;
        }
        parent::report($e);
    }

    /**
     * 未帶/帶了失效令牌訪問需登入接口時，統一回傳未登入結構，避免 500 與異常外洩
     */
    public function render($request, Throwable $e)
    {
        if ($e instanceof OAuthServerException && !$request->is('oauth/*')) {
            return response()->json([
                'status' => 500,
                'msg'    => __('api.no_login'),
            ]);
        }
        // 限流（第4層）按本項目 App 統一格式返回，避免 App 只看到通用的 429 文本
        if ($e instanceof \Illuminate\Http\Exceptions\ThrottleRequestsException) {
            return response()->json([
                'status'  => 429,
                'msg'     => __('api.too_many_requests'),
                'results' => '',
            ]);
        }
        return parent::render($request, $e);
    }

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }
}
