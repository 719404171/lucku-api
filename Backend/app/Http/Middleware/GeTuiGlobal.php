<?php
/*
 * File: Language.php
 * Created Time: 2023-11-27 11:26:05 am
 * Author: Wang Yu Qiang (719404171@qq.com)
 * -----
 * Last Modified: 2024-07-29 4:40:25 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright 2023 - 2023 Brainstorm Ltd, 成都睿創科技有限公司
 */
namespace App\Http\Middleware;

use Closure;
use App\Models\ShopGeTui;
use Illuminate\Support\Facades\App;

class GeTuiGlobal{

    
    protected $app;

    public function __construct(){
        
        $this->app = App::getInstance();
    }
    /**
     * 設置Getui全局變量
     * 
     */
    public function handle($request, Closure $next){
        $ShopGeTui = new ShopGeTui();
        $info = $ShopGeTui->where('getui_status','=',1)->first();
        // 你可以在服务提供者中绑定一个全局变量，然后在应用程序的任何位置使用 app 辅助函数来访问它。
        if(!empty($info)){
            $this->app->instance('global_getui_set', 1);
            $this->app->instance('global_app_id', $info['app_id']);
            $this->app->instance('global_app_secret', $info['app_secret']);
            $this->app->instance('global_app_key', $info['app_key']);
            $this->app->instance('global_master_secret', $info['master_secret']);
            $this->app->instance('global_host', $info['host']);
        }else{
            $this->app->instance('global_getui_set', '');
        }
        return $next($request);
    }
    
    // 然后，你可以在任何地方使用 app 辅助函数来访问它。
    //  $value = app('global_shop_name');

}