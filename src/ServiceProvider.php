<?php

namespace XuanChen\UnionPay;

use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{

    /**
     * Register services.
     * @return void
     */
    public function register()
    {
        $this->app->bind('xuanchen.unionpay', function ($app) {
            $unionpay = new UnionPay();
            $unionpay->setConfig();

            return $unionpay;
        });
    }

    /**
     * Bootstrap services.
     * @return void
     */
    public function boot()
    {
        $this->setConfig();
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations/');

    }

    public function setConfig()
    {
        $path = __DIR__ . '/../config/unionpay.php';

        if ($this->app->runningInConsole()) {
            $this->publishes([$path => config_path('unionpay.php'),]);
            $this->publishes([__DIR__ . '/database/migrations' => database_path('migrations')]);

        }

        $this->mergeConfigFrom($path, 'unionpay');

    }

}
