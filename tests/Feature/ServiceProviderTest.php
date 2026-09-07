<?php

namespace SameOldNick\LaravelSuitcase\Tests\Feature;

use SameOldNick\LaravelSuitcase\Contracts\EnvVariables;
use SameOldNick\LaravelSuitcase\ServiceProvider;
use SameOldNick\LaravelSuitcase\Support\EnvVariables as EnvVariablesImplementation;
use SameOldNick\LaravelSuitcase\Tests\TestCase;

/**
 * These tests exercise the REAL package `ServiceProvider` — the exact path a
 * consumer takes when they `composer require` the package.
 */
class ServiceProviderTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [ServiceProvider::class];
    }

    public function test_package_config_is_merged(): void
    {
        $this->assertSame('laravel_shared_hosting_pack.zip', config('suitcase.zip_name'));
        $this->assertSame('deploy', config('suitcase.export_dir'));
    }

    public function test_env_variables_contract_is_bound(): void
    {
        $this->assertInstanceOf(EnvVariablesImplementation::class, app(EnvVariables::class));
    }

    public function test_pack_command_is_registered(): void
    {
        $this->artisan('suitcase:pack', ['--help'])
            ->expectsOutputToContain('Package Laravel app for deployment to shared hosting')
            ->assertExitCode(0);
    }

    public function test_suitcase_config_can_be_published(): void
    {
        $this->artisan('vendor:publish', [
            '--tag' => 'suitcase-config',
            '--force' => true,
        ])->assertExitCode(0);

        $this->assertFileExists(config_path('suitcase.php'));

        $published = require config_path('suitcase.php');

        $this->assertSame('laravel_shared_hosting_pack.zip', $published['zip_name'] ?? null);
    }

    public function test_shared_env_file_can_be_published(): void
    {
        $this->artisan('vendor:publish', [
            '--tag' => 'suitcase-env',
            '--force' => true,
        ])->assertExitCode(0);

        $this->assertFileExists(base_path('.env.shared'));
        $this->assertStringContainsString('SHARED_HOSTING=true', file_get_contents(base_path('.env.shared')));
    }
}
