<?php

namespace SameOldNick\LaravelSuitcase\Tests\Support;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use SameOldNick\LaravelSuitcase\Commands\PackForSharedHosting;
use SameOldNick\LaravelSuitcase\Config;
use SameOldNick\LaravelSuitcase\Contracts;
use SameOldNick\LaravelSuitcase\Support;

/**
 * Faithful stand-in for the package's `ServiceProvider`.
 *
 * It mirrors the real provider's bindings and command registration, but merges
 * the config from the path that actually exists (`config/suitcase.php`). The
 * real provider is covered separately in `ServiceProviderTest`.
 */
class TestServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/suitcase.php', 'suitcase');

        $this->app->bind(Contracts\EnvVariables::class, Support\EnvVariables::class);
        $this->app->bind(Contracts\Config\PackConfig::class, Config\PackConfig::class);
        $this->app->bind(Contracts\Config\Options::class, Config\Options::class);
        $this->app->bind(Contracts\Config\Repository::class, Config\Repositories\ConfigRepository::class);
        $this->app->bind(Contracts\Config\ValidatesConfig::class, Config\Validators\DefaultValidator::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                PackForSharedHosting::class,
            ]);
        }
    }
}
