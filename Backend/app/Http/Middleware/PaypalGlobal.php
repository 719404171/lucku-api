<?php
/*
 * File: PaypalGlobal.php
 * Project: NewDemo
 * Created Time: 2024-06-13 02:32:26 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-07-29 4:40:18 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2024 成都睿創科技有限公司
 */
namespace App\Http\Middleware;

use Closure;
use App\Models\ShopPaypal;
use Illuminate\Support\Facades\App;

class PaypalGlobal{

    
    protected $app;

    public function __construct(){
        
        $this->app = App::getInstance();
    }
    /**
     * 全局Paypal設置
     * 
     */
    public function handle($request, Closure $next){
        $ShopPaypal = new ShopPaypal();
        $info = $ShopPaypal->where('paypal_status','=',1)->where('paypal_enable','=',1)->first();
        // 你可以在服务提供者中绑定一个全局变量，然后在应用程序的任何位置使用 app 辅助函数来访问它。
        if(!empty($info)){
            $this->app->instance('global_paypal_set', 1);
            $this->app->instance('global_client_id', $info['client_id']);
            $this->app->instance('global_paypal_secret', $info['paypal_secret']);
            $this->app->instance('global_paypal_env', $info['paypal_env']);
        }else{
            $this->app->instance('global_paypal_set', 1);
            $this->app->instance('global_client_id', 'AbNhgz1rwU0mJn7s6M1UZCIhEgT1RT7DKbEAOAPXZ9B3bXeR_anOrH2AwyiACzJ9T8SJaGaSJzgNV3DX');
            $this->app->instance('global_paypal_secret', 'ELB0CMbepT52OfMV3Ed7WuvgcJxTJj17Uj877mJRX_PnaMFjITq6nuSB0iwUsbKiPK8sIN6pfLIxJ_Wy');
            $this->app->instance('global_paypal_env', 2);
        }
        return $next($request);
    }
    
    // 然后，你可以在任何地方使用 app 辅助函数来访问它。
    //  $value = app('global_client_id');

}