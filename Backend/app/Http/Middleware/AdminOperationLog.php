<?php

namespace App\Http\Middleware;

use App\Models\Admins;
use Closure;
use Illuminate\Support\Facades\DB;

/**
 * 後台操作審計：記錄所有寫類請求（POST/PUT/DELETE）
 * 放在 RefreshToken 之後，因此可以拿到已登錄的管理員
 */
class AdminOperationLog
{
    /**
     * 不記錄的參數鍵（避免密碼、令牌落庫）
     */
    private static $sensitiveKeys = [
        'password', 'old_password', 'new_password', 'confirm_password',
        'oldPassword', 'newPassword', 'confirmPassword',
        'sms_code', 'token', 'access_key_id', 'access_key_secret',
    ];

    public function handle($request, Closure $next)
    {
        $response = $next($request);

        try {
            if (in_array($request->method(), ['POST', 'PUT', 'DELETE', 'PATCH'], true)) {
                $adminId = (int)$request->attributes->get('admin_id', 0);
                $admin = $adminId > 0
                    ? Admins::where('admin_id', $adminId)->select('admin_id', 'username')->first()
                    : null;

                $params = $request->except(self::$sensitiveKeys);
                if ($request->hasFile('file')) {
                    $params['file'] = '[uploaded file]';
                }
                $paramsJson = json_encode($params, JSON_UNESCAPED_UNICODE);
                if ($paramsJson === false) {
                    $paramsJson = '';
                }

                DB::table('admin_operation_log')->insert([
                    'admin_id'   => !empty($admin) ? $admin->admin_id : 0,
                    'username'   => !empty($admin) ? $admin->username : '',
                    'method'     => $request->method(),
                    'path'       => mb_substr($request->path(), 0, 250),
                    'params'     => mb_substr($paramsJson, 0, 2000),
                    'ip'         => (string)$request->ip(),
                    'user_agent' => mb_substr((string)$request->header('User-Agent'), 0, 250),
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        } catch (\Throwable $e) {
            // 審計失敗不影響業務請求
        }

        return $response;
    }
}
