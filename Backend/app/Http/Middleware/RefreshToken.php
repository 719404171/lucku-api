<?php
/*
 * File: RefreshToken.php
 * Created Time: 2023-11-14 3:52:07 pm
 * Author: Wang Yu Qiang (719404171@qq.com)
 * -----
 * Last Modified: 2023-11-14 3:52:20 pm
 * Modified By: Wang Yu Qiang (719404171@qq.com>)
 * -----
 * Copyright 2023 - 2023 Brainstorm Ltd, 成都睿創科技有限公司
 */

namespace App\Http\Middleware;
use App\Models\Admins;
use Closure;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Controller;
class RefreshToken extends Controller
{
    /**
     * Handle an incoming request.
     *
     * 安全加固：
     * 1. 數據庫只保存 token 的 sha256，庫被讀走也無法直接調接口
     * 2. 校驗服務端過期時間 token_expire_at（不再永久有效）
     * 3. 校驗結果快取 60 秒（登出/改密碼時主動清除）
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $token = (string)$request->header('TOKEN');
        if ($token === '') {
            return response()->json(["status" => 400, "msg" => "No Operational Permission"]);
        }

        $tokenHash = hash('sha256', $token);
        $cacheKey = 'admin:token:v2:' . md5($token);

        $adminId = Cache::remember($cacheKey, 60, function () use ($tokenHash) {
            $admin = Admins::where('token', $tokenHash)
                ->where('status', 1)
                ->select('admin_id', 'token_expire_at')
                ->first();
            if (empty($admin)) {
                return 0;
            }
            if (empty($admin->token_expire_at) || strtotime($admin->token_expire_at) <= time()) {
                return 0;
            }
            return (int)$admin->admin_id;
        });

        if ($adminId > 0) {
            $request->attributes->set('admin_id', $adminId);
            $request->attributes->set('admin_token_hash', $tokenHash);
            return $next($request);
        }

        Cache::forget($cacheKey);
        return response()->json(["status" => 400, "msg" => "No Operational Permission"]);
    }
}
