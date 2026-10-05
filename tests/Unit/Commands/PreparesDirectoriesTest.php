<?php

namespace SameOldNick\LaravelSuitcase\Tests\Unit\Commands;

use SameOldNick\LaravelSuitcase\Contracts\PackPipelineStep;
use SameOldNick\LaravelSuitcase\Runners\Steps\PreparesDirectories;
use SameOldNick\LaravelSuitcase\Support\Outputters\OutputRecorder;
use SameOldNick\LaravelSuitcase\Tests\TestCase;

/**
 * Unit tests for the `PreparesDirectories` pipeline step.
 */
class PreparesDirectoriesTest extends TestCase
{
    protected function createStep(): PackPipelineStep
    {
        return new PreparesDirectories;
    }

    public function test_prepare_export_directories_creates_expected_tree(): void
    {
        $config = $this->packConfig();
        $outputter = new OutputRecorder;
        $context = $this->createContext($config, outputter: $outputter);
        $step = $this->createStep();

        $step->perform($context);

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

        $messages = array_column($outputter->getMessages(), 'message');
        $this->assertContains('Export directories prepared successfully.', $messages);
    }

    public function test_prepare_export_directory_recreates_existing_directory(): void
    {
        $config = $this->packConfig();
        $context = $this->createContext($config);
        $step = $this->createStep();

        $path = $this->app->basePath('deploy-recreate');

        mkdir($path.'/nested', 0777, true);
        file_put_contents($path.'/stale.txt', 'old');
        file_put_contents($path.'/nested/stale.txt', 'old');

        $this->invoke($step, 'prepareExportDirectory', [$context, $path]);

        $this->assertDirectoryExists($path);
        $this->assertFileDoesNotExist($path.'/stale.txt');
        $this->assertDirectoryDoesNotExist($path.'/nested');
        $this->assertSame("*\n!.gitignore\n", file_get_contents($path.'/.gitignore'));
    }

    public function test_prepare_export_directory_creates_directory_when_missing(): void
    {
        $config = $this->packConfig();
        $context = $this->createContext($config);
        $step = $this->createStep();

        $path = $this->app->basePath('deploy-brand-new');

        $this->assertDirectoryDoesNotExist($path);

        $this->invoke($step, 'prepareExportDirectory', [$context, $path]);

        $this->assertDirectoryExists($path);
        $this->assertSame("*\n!.gitignore\n", file_get_contents($path.'/.gitignore'));
    }

    public function test_create_git_ignore_file_writes_expected_content(): void
    {
        $config = $this->packConfig();
        $context = $this->createContext($config);
        $step = $this->createStep();

        $dir = $this->app->basePath('gitignore-unit');
        mkdir($dir, 0777, true);

        $this->invoke($step, 'createGitIgnoreFile', [$context, $dir]);

        $this->assertSame("*\n!.gitignore\n", file_get_contents($dir.'/.gitignore'));
    }
}
