<?php
/*
 * File: CheckLogin.php
 * Created Time: 2023-11-14 3:49:48 pm
 * Author: Wang Yu Qiang (719404171@qq.com)
 * -----
 * Last Modified: 2023-11-14 3:50:05 pm
 * Modified By: Wang Yu Qiang (719404171@qq.com>)
 * -----
 * Copyright 2023 - 2023 Brainstorm Ltd, 成都睿創科技有限公司
 */
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
class CheckLogin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (Auth::guard('api')->guest()) {
            return response()->json(["status" => 405, "msg" => __('api.no_login')]);
        }
        // 封禁/註銷的帳號（user_status != 1）即使舊 token 未過期也不允許繼續訪問
        $user = Auth::guard('api')->user();
        if ((int)$user->user_status !== 1) {
            $msg = (!empty($user->ban_until) || !empty($user->ban_reason)) ? __('api.account_banned') : __('api.no_login');
            return response()->json(["status" => 405, "msg" => $msg]);
        }
        return $next($request);
    }
}
