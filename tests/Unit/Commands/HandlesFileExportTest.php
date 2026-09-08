<?php

namespace SameOldNick\LaravelSuitcase\Tests\Unit\Commands;

use SameOldNick\LaravelSuitcase\Tests\TestCase;

/**
 * Unit tests for the `HandlesFileExport` concern.
 */
class HandlesFileExportTest extends TestCase
{
    public function test_export_files_copies_laravel_and_public_directories(): void
    {
        $this->givenMinimalLaravelApp();
        $this->givenSharedEnvFile();

        $config = $this->packConfig();
        $command = $this->makePackCommand($config);

        $this->invoke($command, 'prepareExportDirectories');
        $this->invoke($command, 'exportFiles');

        $laravel = $config->getLaravelPath();
        $public = $config->getPublicPath();

        // Laravel files (from the include list).
        $this->assertFileExists($laravel.'/artisan');
        $this->assertFileExists($laravel.'/composer.json');
        $this->assertFileExists($laravel.'/app/Models/User.php');
        $this->assertFileExists($laravel.'/config/custom.php');
        $this->assertFileExists($laravel.'/routes/web.php');

        // .env is exported from .env.shared unless --skip-env.
        $this->assertFileExists($laravel.'/.env');

        // Public directory contents.
        $this->assertFileExists($public.'/.htaccess');
        $this->assertFileExists($public.'/build/assets/app.js');

        // Stub-generated front controller + constants.
        $this->assertFileExists($public.'/constants.php');
        $this->assertFileExists($public.'/index.php');
        $this->assertStringContainsString('constants.php', file_get_contents($public.'/index.php'));
    }

    public function test_export_files_respects_skip_env_option(): void
    {
        $this->givenMinimalLaravelApp();
        $this->givenSharedEnvFile();

        $config = $this->packConfig();
        $command = $this->makePackCommand($config, ['--skip-env' => true]);

        $this->invoke($command, 'prepareExportDirectories');
        $this->invoke($command, 'exportFiles');

        $this->assertFileDoesNotExist($config->getLaravelPath().'/.env');
        $this->assertFileExists($config->getPublicPath().'/index.php');
    }

    public function test_export_public_directory_applies_public_excludes(): void
    {
        $this->givenMinimalLaravelApp();

        // These should be excluded from the public export.
        file_put_contents($this->app->basePath('public/.gitignore'), 'ignored');
        file_put_contents($this->app->basePath('public/hot'), 'hot-file');

        $config = $this->packConfig();
        $command = $this->makePackCommand($config);

        $this->invoke($command, 'prepareExportDirectories');
        $this->invoke($command, 'exportPublicDirectory', [$this->app->basePath('public')]);

        $public = $config->getPublicPath();

        $this->assertFileExists($public.'/.htaccess');
        $this->assertFileDoesNotExist($public.'/.gitignore');
        $this->assertFileDoesNotExist($public.'/hot');
    }

    public function test_copy_directory_honors_excludes(): void
    {
        $command = $this->makePackCommand($this->packConfig());

        $source = $this->createSourceTree();
        $destination = $this->app->basePath('copydst-excludes');

        $this->invoke($command, 'copyDirectory', [$source, $destination, [], ['sub/drop.txt']]);

        $this->assertFileExists($destination.'/root.txt');
        $this->assertFileExists($destination.'/keep.txt');
        $this->assertFileExists($destination.'/sub/keep.txt');
        $this->assertFileDoesNotExist($destination.'/sub/drop.txt');
    }

    public function test_copy_directory_honors_includes(): void
    {
        $command = $this->makePackCommand($this->packConfig());

        $source = $this->createSourceTree();
        $destination = $this->app->basePath('copydst-includes');

        $this->invoke($command, 'copyDirectory', [$source, $destination, ['root.txt'], []]);

        $this->assertFileExists($destination.'/root.txt');
        $this->assertFileDoesNotExist($destination.'/keep.txt');
        $this->assertFileDoesNotExist($destination.'/sub');
    }

    public function test_copy_directory_with_basename_exclude_matches_nested_files(): void
    {
        $command = $this->makePackCommand($this->packConfig());

        $source = $this->createSourceTree();
        $destination = $this->app->basePath('copydst-basename');

        // A bare `keep.txt` exclude should also match the nested copy via basename.
        $this->invoke($command, 'copyDirectory', [$source, $destination, [], ['keep.txt']]);

        $this->assertFileExists($destination.'/root.txt');
        $this->assertFileExists($destination.'/sub/drop.txt');
        $this->assertFileDoesNotExist($destination.'/sub/keep.txt');
        $this->assertFileDoesNotExist($destination.'/keep.txt');
    }

    /**
     * Create a small source tree used by the copyDirectory tests.
     */
    protected function createSourceTree(): string
    {
        $source = $this->app->basePath('copysrc');

        static::removeDirectory($source);

        mkdir($source.'/sub', 0777, true);
        file_put_contents($source.'/root.txt', 'root');
        file_put_contents($source.'/keep.txt', 'keep');
        file_put_contents($source.'/sub/keep.txt', 'keep');
        file_put_contents($source.'/sub/drop.txt', 'drop');

        return $source;
    }
}
