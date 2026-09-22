<?php

namespace SameOldNick\LaravelSuitcase\Tests\Feature;

use Illuminate\Support\Facades\Route;
use SameOldNick\LaravelSuitcase\ServiceProvider;
use SameOldNick\LaravelSuitcase\Tests\TestCase;

/**
 * Exercises the shared-hosting storage helper route registered by the real
 * `ServiceProvider` when `SHARED_HOSTING=true`.
 */
class StorageRouteTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [ServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('suitcase.shared_hosting', true);
    }

    public function test_storage_route_registers_at_configured_uri(): void
    {
        $uri = ltrim((string) config('suitcase.storage_route'), '/');

        $registered = collect(Route::getRoutes()->getRoutes())
            ->contains(fn ($route) => $route->uri() === $uri);

        $this->assertTrue($registered, 'The storage helper route should register when SHARED_HOSTING is enabled.');
    }
}
