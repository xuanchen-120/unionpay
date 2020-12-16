<?php

namespace XuanChen\UnionPay;

use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{

    /**
     * Bootstrap services.
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__ . '/../config/unionpay.php' => config_path('unionpay.php')]);
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations/');

        }
        $this->loadRoutesFrom(__DIR__ . '/routes.php');

    }

    /**
     * Notes: 部署时加载
     * @Author: 玄尘
     * @Date  : 2020/12/11 16:50
     */
    public function register()
    {
        $this->app->bind('xuanchen.unionpay', function ($app) {
            $unionpay = new UnionPay();
            $unionpay->setConfig();

            return $unionpay;
        });
        
        $this->mergeConfigFrom(__DIR__ . '/../config/unionpay.php', 'unionpay');
    }

}
