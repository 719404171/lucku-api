<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;

/**
 * 記錄已登錄用戶的 API 請求（user_id + 真實IP + 接口），
 * 用於攻擊溯源：把真實IP與賬號關聯，定位惡意賬號。
 * 僅記錄帶有效 token 的請求，未登錄流量（如 OPTIONS 預檢）不記，避免無意義寫庫。
 */
class LogUserRequest
{
    public function handle($request, Closure $next)
    {
        return $next($request);
    }

    /**
     * 響應發送後執行（此時 auth 中間件已解析出用戶），不阻塞正常請求
     */
    public function terminate($request, $response)
    {
        try {
            if ($request->getMethod() === 'OPTIONS') {
                return;
            }
            $user = auth('api')->user();
            if (empty($user) || empty($user->user_id)) {
                return;
            }
            DB::table('user_request_log')->insert([
                'user_id'    => $user->user_id,
                'ip'         => (string)$request->ip(),
                'method'     => $request->getMethod(),
                'path'       => substr('/' . ltrim($request->path(), '/'), 0, 255),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // 記錄失敗不得影響正常業務
        }
    }
}
