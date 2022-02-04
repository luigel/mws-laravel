<?php

namespace Luigel\AmazonMws;

use Illuminate\Support\ServiceProvider;

class AmazonMwsServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $configPath = __DIR__ . '/config/amazon-mws.php';
        $this->mergeConfigFrom($configPath, 'amazon-mws');

        $this->app->alias('AmazonOrderList', 'Luigel\AmazonMws\AmazonOrderList');
        $this->app->alias('AmazonOrderItemList', 'Luigel\AmazonMws\AmazonOrderItemList');
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $configPath = __DIR__ . '/../../config/amazon-mws.php';
            $this->publishes([$configPath => config_path('amazon-mws.php')], 'config');
        }
    }
}
