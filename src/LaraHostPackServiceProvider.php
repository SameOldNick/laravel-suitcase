<?php

namespace SameOldNick\LaraHostPack;

use Illuminate\Support\ServiceProvider;

class LaraHostPackServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            Contracts\PackConfig::class,
            Support\PackConfig::class
        );

        $this->app->bind(
            Contracts\EnvVariables::class,
            Support\EnvVariables::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/hostpack.php' => config_path('hostpack.php'),
        ], 'larahostpack-config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\PackForSharedHosting::class,
            ]);
        }

        if (config('hostpack.shared_hosting')) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        }
    }
}
