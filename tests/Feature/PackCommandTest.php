<?php

namespace SameOldNick\LaravelSuitcase\Tests\Feature;

use SameOldNick\LaravelSuitcase\Tests\TestCase;

class PackCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The test environment has no MySQL server; isolate the file-packaging pipeline.
        config(['suitcase.db_dump.enabled' => false]);

        // Tests share one base path per class, so clear artifacts from previous runs.
        $base = $this->app->basePath();

        @unlink($base.DIRECTORY_SEPARATOR.'laravel_shared_hosting_pack.zip');
        @unlink($base.DIRECTORY_SEPARATOR.'.env.shared');
        static::removeDirectory($base.DIRECTORY_SEPARATOR.'deploy');
    }

    public function test_pack_fails_when_shared_env_file_is_missing(): void
    {
        // Intentionally do NOT create `.env.shared`.

        $this->artisan('suitcase:pack')
            ->expectsOutputToContain('Configuration error')
            ->assertExitCode(1);

        $this->assertFileDoesNotExist($this->app->basePath('laravel_shared_hosting_pack.zip'));
    }

    public function test_pack_creates_zip_with_expected_contents(): void
    {
        $this->givenMinimalLaravelApp();
        $this->givenSharedEnvFile();

        $this->artisan('suitcase:pack')
            ->expectsConfirmation('Do you want to continue?', 'yes')
            ->assertExitCode(0);

        $zipPath = $this->app->basePath('laravel_shared_hosting_pack.zip');

        $this->assertFileExists($zipPath, 'The packer should create the ZIP at the configured path.');

        $entries = $this->zipEntries($zipPath);

        foreach ([
            'INSTALL.txt',
            'public_html/index.php',
            'public_html/constants.php',
            'laravel/.env',
            'laravel/artisan',
            'laravel/composer.json',
            'laravel/app/Models/User.php',
            'laravel/config/custom.php',
            'laravel/routes/web.php',
            'laravel/bootstrap/cache/packages.php',
        ] as $expected) {
            $this->assertContains($expected, $entries, "Expected ZIP to contain: {$expected}");
        }

        // Database dump was disabled, so no SQL dump should be present.
        $this->assertNotContains('database.sql', $entries);
    }

    public function test_pack_exports_public_directory(): void
    {
        $this->givenMinimalLaravelApp();
        $this->givenSharedEnvFile();

        $this->artisan('suitcase:pack')
            ->expectsConfirmation('Do you want to continue?', 'yes')
            ->assertExitCode(0);

        $zipPath = $this->app->basePath('laravel_shared_hosting_pack.zip');

        // The packer intentionally replaces the front controller with its
        // shared-hosting stub that points to LARAVEL_ROOT_DIR.
        $this->assertStringContainsString(
            "require_once __DIR__ . '/constants.php';",
            $this->zipEntryContents($zipPath, 'public_html/index.php')
        );

        // Other public assets are copied verbatim.
        $this->assertSame(
            'RewriteEngine On',
            $this->zipEntryContents($zipPath, 'public_html/.htaccess')
        );

        $this->assertSame(
            'console.log("hi");',
            $this->zipEntryContents($zipPath, 'public_html/build/assets/app.js')
        );
    }

    public function test_constants_file_points_to_remote_laravel_path(): void
    {
        $this->givenMinimalLaravelApp();
        $this->givenSharedEnvFile();

        $this->artisan('suitcase:pack')
            ->expectsConfirmation('Do you want to continue?', 'yes')
            ->assertExitCode(0);

        $constants = $this->zipEntryContents(
            $this->app->basePath('laravel_shared_hosting_pack.zip'),
            'public_html/constants.php'
        );

        $this->assertStringContainsString(
            "define('LARAVEL_ROOT_DIR', '/home/username/laravel');",
            $constants
        );
    }

    public function test_env_file_is_updated_with_shared_hosting_variables(): void
    {
        $this->givenMinimalLaravelApp();
        $this->givenSharedEnvFile("APP_NAME=MyApp\n");

        $this->artisan('suitcase:pack')
            ->expectsConfirmation('Do you want to continue?', 'yes')
            ->assertExitCode(0);

        $env = $this->zipEntryContents(
            $this->app->basePath('laravel_shared_hosting_pack.zip'),
            'laravel/.env'
        );

        $this->assertStringContainsString('SHARED_HOSTING=true', $env);
        $this->assertStringContainsString('APP_ENV=production', $env);
        $this->assertStringContainsString('APP_DEBUG=false', $env);
        $this->assertMatchesRegularExpression('/^APP_KEY=base64:.+/m', $env);
    }

    public function test_install_txt_contains_deployment_instructions(): void
    {
        $this->givenMinimalLaravelApp();
        $this->givenSharedEnvFile();

        $this->artisan('suitcase:pack')
            ->expectsConfirmation('Do you want to continue?', 'yes')
            ->assertExitCode(0);

        $install = $this->zipEntryContents(
            $this->app->basePath('laravel_shared_hosting_pack.zip'),
            'INSTALL.txt'
        );

        $this->assertStringContainsString('/home/username/laravel', $install);
        $this->assertStringContainsString('/home/username/public_html', $install);
        $this->assertStringContainsString('schedule:run', $install);

        // Database deployment guidance is included in the install steps.
        $this->assertStringContainsString('Create a MySQL database', $install);
        $this->assertStringContainsString('phpMyAdmin', $install);
        $this->assertStringContainsString('DB_USERNAME', $install);
        $this->assertStringContainsString('DB_DATABASE', $install);
    }

    public function test_skip_env_option_omits_the_env_file(): void
    {
        $this->givenMinimalLaravelApp();
        $this->givenSharedEnvFile();

        $this->artisan('suitcase:pack', ['--skip-env' => true])
            ->expectsConfirmation('Do you want to continue?', 'yes')
            ->assertExitCode(0);

        $zipPath = $this->app->basePath('laravel_shared_hosting_pack.zip');

        $this->assertFileExists($zipPath);
        $this->assertNotContains('laravel/.env', $this->zipEntries($zipPath));
        $this->assertContains('public_html/index.php', $this->zipEntries($zipPath));
    }

    public function test_declining_confirmation_aborts_without_creating_zip(): void
    {
        $this->givenMinimalLaravelApp();
        $this->givenSharedEnvFile();

        $this->artisan('suitcase:pack')
            ->expectsConfirmation('Do you want to continue?', 'no')
            ->assertExitCode(0);

        $this->assertFileDoesNotExist($this->app->basePath('laravel_shared_hosting_pack.zip'));
    }
}
