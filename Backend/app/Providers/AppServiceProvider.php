<?php
/*
 * File: AppServiceProvider.php
 * Created Time: 2024-05-29 11:27:52 am
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-07-29 5:25:22 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2024 成都睿創科技有限公司
 */



namespace App\Providers;

use App\Models\ShopStripe;
use Laravel\Cashier\Cashier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use App\Http\Controllers\Common\MonologsController  as Logs;//日志类

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // 隊列 worker / CLI 上下文沒有 HTTP 的 Language 中間件，
        // 提供預設值避免各 Model 建構子 app('global_language') 解析失敗
        // （HTTP 請求仍會由 App\Http\Middleware\Language 覆蓋為實際語言）
        if (!$this->app->bound('global_language')) {
            $this->app->instance('global_language', 'zh');
        }
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() {
        // Stripe支付設置
        // 數據庫不可用時（部署期、DB 抖動）不應讓整個應用啟動失敗，捕獲異常後回退到默認配置
        $info = null;
        try {
            $ShopStripe = new ShopStripe();
            $info = $ShopStripe->where('stripe_status', 1)->first();
        } catch (\Throwable $e) {
            Logs::error('讀取 Stripe 配置失敗，暫用默認配置：' . $e->getMessage());
        }

        if(!empty($info)){
            config(['cashier.key' => $info['stripe_key']]);
            config(['cashier.secret' => $info['stripe_secret']]);
        }else{
            $stripe_key=config('services.stripe.key');
            $stripe_secret=config('services.stripe.secret');
            config(['cashier.key' => $stripe_key]);
            config(['cashier.secret' => $stripe_secret]);
        }
        //打印sql语句到日志
        $logSql = env('SQL_QUERY_SAVE', 'false');
        if($logSql){
            DB::listen(function($query){
                //剔除命令行sql
                if(app()->runningInConsole()) return;
                $request = request();
                //防止重复录入形成死循环 sql_log有关的表不显示
                if(strpos($query->sql,'sql_log' !=false)) return;
                $sql = $query->sql;
                $bindings = $query->bindings;
                foreach ($bindings as $binding) {
                    $sql = preg_replace('/\?/', is_numeric($binding) ? $binding : "'$binding'", $sql, 1);
                }
                
                Logs::info('sql语句:'.$sql.';', ['查询完成'] ,'SqlQuery'.date('Y-m-d') ,false);
            });
        }
    }




    


    
}
