<?php
/*
 * File: CheckToken.php
 * Created Time: 2023-11-14 3:50:15 pm
 * Author: Wang Yu Qiang (719404171@qq.com)
 * -----
 * Last Modified: 2023-11-14 3:50:29 pm
 * Modified By: Wang Yu Qiang (719404171@qq.com>)
 * -----
 * Copyright 2023 - 2023 Brainstorm Ltd, 成都睿創科技有限公司
 */
namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Controller;
class CheckToken extends Controller
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
        $user = auth('api')->user();
        if(empty($user->user_id)){
            return response()->json(["status" => 400, "msg" => "No Operational Permission"]);
        }
        // F-11：用戶有效性快取 5 分鐘，避免每個請求都查庫
        $uid = $user->user_id;
        $valid = Cache::remember('user:active:'.$uid, 300, function () use ($uid) {
            $row = User::where('user_id', $uid)->select('user_status', 'ban_until')->first();
            if (empty($row)) {
                return false;
            }
            if ((int)$row->user_status === 1 && empty($row->ban_until)) {
                return true;
            }
            // 臨時封禁到期自動解封（ban_until 有值說明是封禁導致，不是郵箱未驗證）
            if (!empty($row->ban_until) && strtotime($row->ban_until) <= time()) {
                User::where('user_id', $uid)->update([
                    'user_status' => 1,
                    'ban_until'   => null,
                    'ban_reason'  => null,
                    'updated_at'  => date('Y-m-d H:i:s'),
                ]);
                return true;
            }
            return false;
        });
        if($valid){
            return $next($request);
        }else{
            return response()->json(["status" => 400, "msg" => "No Operational Permission"]);
        }
    }
}
