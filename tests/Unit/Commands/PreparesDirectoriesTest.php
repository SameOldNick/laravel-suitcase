<?php

namespace SameOldNick\LaravelSuitcase\Tests\Unit\Commands;

use SameOldNick\LaravelSuitcase\Tests\TestCase;

/**
 * Unit tests for the `PreparesDirectories` concern.
 */
class PreparesDirectoriesTest extends TestCase
{
    public function test_prepare_export_directories_creates_expected_tree(): void
    {
        $config = $this->packConfig();
        $command = $this->makePackCommand($config);

        $this->invoke($command, 'prepareExportDirectories');

        $this->assertDirectoryExists($config->getExportPath());
        $this->assertDirectoryExists($config->getPublicPath());
        $this->assertDirectoryExists($config->getLaravelPath());

        foreach ([
            'bootstrap/cache',
            'storage/app',
            'storage/framework/cache',
            'storage/framework/sessions',
            'storage/framework/views',
            'storage/logs',
        ] as $dir) {
            $laravelSubPath = $config->getLaravelPath().'/'.$dir;

            $this->assertDirectoryExists($laravelSubPath);
            $this->assertSame("*\n!.gitignore\n", file_get_contents($laravelSubPath.'/.gitignore'));
        }

        $this->assertSame("*\n!.gitignore\n", file_get_contents($config->getExportPath().'/.gitignore'));

        $this->assertStringContainsString('Export directories prepared successfully.', $this->commandOutput($command));
    }

    public function test_prepare_export_directory_recreates_existing_directory(): void
    {
        $config = $this->packConfig();
        $command = $this->makePackCommand($config);

        $path = $this->app->basePath('deploy-recreate');

        mkdir($path.'/nested', 0777, true);
        file_put_contents($path.'/stale.txt', 'old');
        file_put_contents($path.'/nested/stale.txt', 'old');

        $this->invoke($command, 'prepareExportDirectory', [$path]);

        $this->assertDirectoryExists($path);
        $this->assertFileDoesNotExist($path.'/stale.txt');
        $this->assertDirectoryDoesNotExist($path.'/nested');
        $this->assertSame("*\n!.gitignore\n", file_get_contents($path.'/.gitignore'));
    }

    public function test_prepare_export_directory_creates_directory_when_missing(): void
    {
        $config = $this->packConfig();
        $command = $this->makePackCommand($config);

        $path = $this->app->basePath('deploy-brand-new');

        $this->assertDirectoryDoesNotExist($path);

        $this->invoke($command, 'prepareExportDirectory', [$path]);

        $this->assertDirectoryExists($path);
        $this->assertSame("*\n!.gitignore\n", file_get_contents($path.'/.gitignore'));
    }

    public function test_create_git_ignore_file_writes_expected_content(): void
    {
        $config = $this->packConfig();
        $command = $this->makePackCommand($config);

        $dir = $this->app->basePath('gitignore-unit');
        mkdir($dir, 0777, true);

        $this->invoke($command, 'createGitIgnoreFile', [$dir]);

        $this->assertSame("*\n!.gitignore\n", file_get_contents($dir.'/.gitignore'));
    }
}
