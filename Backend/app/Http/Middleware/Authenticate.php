<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo($request)
    {
        return $request->expectsJson() ? null : route('login');
    }



    /**
    * 成功返回数据
    */
    protected function success($param = ""){

        return ["status" => 200, "msg" => "Success", "data" => $param];
    }



}
