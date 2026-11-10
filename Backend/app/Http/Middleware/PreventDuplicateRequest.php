<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * App 防重複請求（第3層）：同一用戶（未登入時按 IP）+ 同一路徑 + 同一參數的並發請求，只放行一個。
 *
 * - 指紋包含用戶 ID，避免同一 IP 下不同用戶互相誤判
 * - 只擋「同時在途」的請求：請求處理完就釋放鎖，用戶正常重試（含登錄失敗後重試）不受影響
 * - 異常時同樣釋放鎖，避免報錯後把用戶鎖在鎖定期內
 * - 命中時按本項目統一的 App 返回格式返回 status=429，不拋 500
 */
class PreventDuplicateRequest
{
    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  int  $seconds 鎖最長持有秒數（僅作兜底，正常會在請求結束時釋放）
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $seconds = 3)
    {
        $seconds = (int)$seconds;
        if ($seconds <= 0) {
            $seconds = 3;
        }

        $user = Auth::guard('api')->user();
        $userId = (int)($user->user_id ?? 0);

        $fingerprint = md5(
            ($userId > 0 ? 'u'.$userId : 'ip'.$request->ip())
            .'|'.$request->path()
            .'|'.json_encode($request->all())
        );

        try {
            $lock = Cache::lock('dup_req:'.$fingerprint, $seconds);
            $acquired = $lock->get();
        } catch (\Throwable $e) {
            // 快取驅動不支援鎖時不阻斷業務，交由第5層唯一索引兜底
            return $next($request);
        }

        if (!$acquired) {
            return response()->json([
                'status'  => 429,
                'msg'     => __('api.too_many_requests'),
                'results' => '',
            ]);
        }

        try {
            return $next($request);
        } finally {
            try {
                $lock->release();
            } catch (\Throwable $e) {
                // 釋放失敗時交由鎖的 TTL 自動過期
            }
        }
    }
}
