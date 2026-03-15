<?php

namespace Arshad1114\DmsDiskServer;

use Arshad1114\DmsDiskServer\Http\Middleware\DmsTokenAuth;
use Illuminate\Support\ServiceProvider;

class DmsServerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/dms-disk-server.php',
            'dms-disk-server'
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/dms-disk-server.php' => config_path('dms-disk-server.php'),
            ], 'dms-disk-server-config');
        }

        $this->app['router']->aliasMiddleware('dms.server.auth', DmsTokenAuth::class);

        $this->loadRoutesFrom(__DIR__ . '/routes/dms-server.php');
    }
}
