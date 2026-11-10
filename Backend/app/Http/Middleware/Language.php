<?php
/*
 * File: Language.php
 * Created Time: 2023-11-27 11:26:05 am
 * Author: Wang Yu Qiang (719404171@qq.com)
 * -----
 * Last Modified: 2024-06-21 3:52:12 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright 2023 - 2023 Brainstorm Ltd, 成都睿創科技有限公司
 */
namespace App\Http\Middleware;
use Closure;
use Session;
use Illuminate\Support\Facades\App;

class Language
{

    
    protected $app;

    public function __construct(){
        
        $this->app = App::getInstance();
    }

    // /**
    //  * 設置當前語言
    //  * zh 中文
    //  * en 英文
    //  * @param  \Illuminate\Http\Request  $request
    //  * @param  \Closure  $next
    //  * @return mixed
    //  */
    public function handle($request, Closure $next){
        $language = $request->header('language');
        if (!empty($language)) {
            App::setLocale($language);
            $this->app->instance('global_language', $language);
        } else {
            App::setLocale('zh');
            $this->app->instance('global_language', 'zh');
        }
        return $next($request);
    }

}