<?php
/*
 * File: EnableCrossRequestMiddleware.php
 * Created Time: 2023-11-14 3:50:42 pm
 * Author: Wang Yu Qiang (719404171@qq.com)
 * -----
 * Last Modified: 2024-08-02 10:09:54 am
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright 2023 - 2023 Brainstorm Ltd, 成都睿創科技有限公司
 */
namespace App\Http\Middleware;

use Closure;

class EnableCrossRequestMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        $origin = $request->server('HTTP_ORIGIN') ? $request->server('HTTP_ORIGIN') : '*';

        if (isset($request->server()["HTTP_ORIGIN"])) {
            $httpOrigin = $request->server()["HTTP_ORIGIN"];
        } else {
            $httpOrigin = "*";
        }

        $allow_origin = [
            $httpOrigin,//允许访问
        ];
        if (in_array($origin, $allow_origin)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Headers', 'Language, Origin, Content-Type, Cookie, X-CSRF-TOKEN, Accept, Authorization, X-XSRF-TOKEN, X-Requested-With, TOKEN');
            $response->headers->set('Access-Control-Expose-Headers', 'Authorization, authenticated, X-Maintenance-Enabled');
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PATCH, PUT, OPTIONS');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        }

        if ($request->getMethod() == "OPTIONS") {
            $header["Access-Control-Allow-Origin"] = $origin;
            $header["Access-Control-Allow-Headers"] = 'Language, Origin, Content-Type, Cookie, X-CSRF-TOKEN, Accept, Authorization, X-XSRF-TOKEN, X-Requested-With, TOKEN';
            $header["Access-Control-Expose-Headers"] = 'Authorization, authenticated, X-Maintenance-Enabled';
            $header["Access-Control-Allow-Credentials"] = 'true';
            return \Response::make('ok', 204, $header);
        }

        return $response;
    }
}
