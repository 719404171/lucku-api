<?php
/*
 * 维护公告状态响应头中间件（APP 端）
 *
 * 在每个 APP 端 API 响应头写入 X-Maintenance-Enabled: 0/1，
 * APP 每次请求接口即可得知是否应进入维护状态，无需再轮询。
 * 处于维护中时，APP 再调一次 GET /api/maintenance/status 拉取公告内容即可。
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MaintenanceNoticeHeader
{
    /** 缓存键 */
    const CACHE_KEY = 'maintenance_enabled';

    /** 缓存有效期（秒）；后台保存时会主动清除，保证切换即时生效 */
    const CACHE_TTL = 300;

    public function handle($request, Closure $next)
    {
        $response = $next($request);

        $response->headers->set('X-Maintenance-Enabled', (string) self::enabled());

        return $response;
    }

    /**
     * 读取维护开关：优先走 Redis 缓存，缓存不可用时回退直查数据库
     *
     * 注意：本项目 CACHE_DRIVER=array（单请求内存缓存），默认 store 无法跨请求复用，
     * 因此这里显式使用 redis store；redis 异常时直接查库兜底，不影响接口可用性。
     */
    public static function enabled()
    {
        $fetch = function () {
            return (int) DB::table('maintenance_notice')->where('id', 1)->value('maintenance_enabled');
        };

        try {
            return (int) Cache::store('redis')->remember(self::CACHE_KEY, self::CACHE_TTL, $fetch);
        } catch (\Throwable $e) {
            return $fetch();
        }
    }

    /**
     * 清除缓存（后台保存维护公告后调用，确保切换即时生效）
     */
    public static function forget()
    {
        try {
            Cache::store('redis')->forget(self::CACHE_KEY);
        } catch (\Throwable $e) {
            // 缓存不可用，无需处理
        }
    }
}
