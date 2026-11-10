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
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        $token=$request->header('TOKEN');
        if (empty($token)) {
            return response()->json(["status" => 400, "msg" => "No Operational Permission"]);
        }
        // F-11：token 有效性快取 5 分鐘，避免每個請求都查庫
        $valid = Cache::remember('admin:token:'.md5($token), 300, function () use ($token) {
            return Admins::where('token',$token)->where('status',1)->exists();
        });
        if(!empty($valid)){
            return $next($request);
        }else{
            return response()->json(["status" => 400, "msg" => "No Operational Permission"]);
        }

    }
}
