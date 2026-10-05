<?php

namespace SameOldNick\LaravelSuitcase\Tests\Unit\Commands;

use SameOldNick\LaravelSuitcase\Contracts\PackPipelineStep;
use SameOldNick\LaravelSuitcase\Runners\Steps\HandlesFileExport;
use SameOldNick\LaravelSuitcase\Runners\Steps\PreparesDirectories;
use SameOldNick\LaravelSuitcase\Tests\TestCase;

/**
 * Unit tests for the `HandlesFileExport` pipeline step.
 */
class HandlesFileExportTest extends TestCase
{
    protected function createStep(): PackPipelineStep
    {
        return new HandlesFileExport;
    }

    public function test_export_files_copies_laravel_and_public_directories(): void
    {
        $this->givenMinimalLaravelApp();
        $this->givenSharedEnvFile();

        $config = $this->packConfig();
        $context = $this->createContext($config);
        $step = $this->createStep();

        (new PreparesDirectories)->perform($context);
        $step->perform($context);

        $laravel = $config->getLaravelPath();
        $public = $config->getPublicPath();

        // Laravel files (from the include list).
        $this->assertFileExists($laravel.'/artisan');
        $this->assertFileExists($laravel.'/composer.json');
        $this->assertFileExists($laravel.'/app/Models/User.php');
        $this->assertFileExists($laravel.'/config/custom.php');
        $this->assertFileExists($laravel.'/routes/web.php');

        // .env is exported from .env.shared unless env export is skipped.
        $this->assertFileExists($laravel.'/.env');

        // Public directory contents.
        $this->assertFileExists($public.'/.htaccess');
        $this->assertFileExists($public.'/build/assets/app.js');

        // Stub-generated front controller + constants.
        $this->assertFileExists($public.'/constants.php');
        $this->assertFileExists($public.'/index.php');
        $this->assertStringContainsString('constants.php', file_get_contents($public.'/index.php'));
    }

    public function test_export_files_respects_skip_env_config(): void
    {
        $this->givenMinimalLaravelApp();
        $this->givenSharedEnvFile();

        $config = $this->packConfig(['skip.env' => true]);
        $context = $this->createContext($config);
        $step = $this->createStep();

        (new PreparesDirectories)->perform($context);
        $step->perform($context);

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
        $context = $this->createContext($config);
        $step = $this->createStep();

        $this->invoke($step, 'exportPublicDirectory', [$context, $this->app->basePath('public')]);

        $public = $config->getPublicPath();

        $this->assertFileExists($public.'/.htaccess');
        $this->assertFileDoesNotExist($public.'/.gitignore');
        $this->assertFileDoesNotExist($public.'/hot');
    }

    public function test_copy_directory_honors_excludes(): void
    {
        $context = $this->createContext($this->packConfig());
        $step = $this->createStep();

        $source = $this->createSourceTree();
        $destination = $this->app->basePath('copydst-excludes');

        $this->invoke($step, 'copyDirectory', [$context, $source, $destination, [], ['sub/drop.txt']]);

        $this->assertFileExists($destination.'/root.txt');
        $this->assertFileExists($destination.'/keep.txt');
        $this->assertFileExists($destination.'/sub/keep.txt');
        $this->assertFileDoesNotExist($destination.'/sub/drop.txt');
    }

    public function test_copy_directory_honors_includes(): void
    {
        $context = $this->createContext($this->packConfig());
        $step = $this->createStep();

        $source = $this->createSourceTree();
        $destination = $this->app->basePath('copydst-includes');

        $this->invoke($step, 'copyDirectory', [$context, $source, $destination, ['root.txt'], []]);

        $this->assertFileExists($destination.'/root.txt');
        $this->assertFileDoesNotExist($destination.'/keep.txt');
        $this->assertFileDoesNotExist($destination.'/sub');
    }

    public function test_copy_directory_with_basename_exclude_matches_nested_files(): void
    {
        $context = $this->createContext($this->packConfig());
        $step = $this->createStep();

        $source = $this->createSourceTree();
        $destination = $this->app->basePath('copydst-basename');

        // A bare `keep.txt` exclude should also match the nested copy via basename.
        $this->invoke($step, 'copyDirectory', [$context, $source, $destination, [], ['keep.txt']]);

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
