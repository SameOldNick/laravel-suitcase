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
        $this->registerConfig();

        $this->app->bind(
            Contracts\EnvVariables::class,
            Support\EnvVariables::class
        );
    }

    /**
     * Register the package configuration.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/larahostpack.php',
            'larahostpack'
        );

        $this->app->bind(Contracts\Config\PackConfig::class, Config\PackConfig::class);
        $this->app->bind(Contracts\Config\Options::class, Config\Options::class);
        $this->app->bind(Contracts\Config\Repository::class, Config\Repositories\ConfigRepository::class);
        $this->app->bind(Contracts\Config\ValidatesConfig::class, Config\Validators\DefaultValidator::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/larahostpack.php' => config_path('larahostpack.php'),
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
