<?php
/*
 * File: Language.php
 * Created Time: 2023-11-27 11:26:05 am
 * Author: Wang Yu Qiang (719404171@qq.com)
 * -----
 * Last Modified: 2024-07-29 4:40:07 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright 2023 - 2023 Brainstorm Ltd, 成都睿創科技有限公司
 */
namespace App\Http\Middleware;

use Closure;
use App\Models\ShopSetting;
use Illuminate\Support\Facades\App;

class ShopGlobal{

    
    protected $app;

    public function __construct(){
        
        $this->app = App::getInstance();
    }
    /**
     * 設置店鋪設置全局變量
     * 
     */
    public function handle($request, Closure $next){
        $ShopSetting = new ShopSetting();
        $info = $ShopSetting->where('shop_status','=',1)->first();
        // 你可以在服务提供者中绑定一个全局变量，然后在应用程序的任何位置使用 app 辅助函数来访问它。
        if(!empty($info)){
            if(empty($info['is_freight'])){
                $info['freight_price']=0;
            }
            if(empty($info['is_tostore'])){
                $info['tostore_price']=0;
            }
            $this->app->instance('global_shop_set', 1);
            $this->app->instance('global_shop_name', $info['shop_name']);
            $this->app->instance('global_shop_address', $info['shop_address']);
            $this->app->instance('global_shop_phone', $info['shop_phone']);
            $this->app->instance('global_shop_mail', $info['shop_mail']);
            $this->app->instance('global_shop_logo', $info['shop_logo']);
            $this->app->instance('global_is_freight', $info['is_freight']);
            $this->app->instance('global_freight_price', $info['freight_price']);
            $this->app->instance('global_is_tostore', $info['is_tostore']);
            $this->app->instance('global_tostore_price', $info['tostore_price']);
            $this->app->instance('global_userlogin_mechanism', $info['userlogin_mechanism'] ?? 2);
        }else{
            $this->app->instance('global_shop_set', '');
            $this->app->instance('global_shop_name', '');
            $this->app->instance('global_shop_address', '');
            $this->app->instance('global_shop_phone', '');
            $this->app->instance('global_shop_mail', '');
            $this->app->instance('global_shop_logo', '');
            $this->app->instance('global_is_freight', 0);
            $this->app->instance('global_freight_price', 0);
            $this->app->instance('global_is_tostore', 0);
            $this->app->instance('global_tostore_price', 0);
            $this->app->instance('global_userlogin_mechanism', 2);
        }
        return $next($request);
    }
    
    // 然后，你可以在任何地方使用 app 辅助函数来访问它。
    //  $value = app('global_shop_name');

}