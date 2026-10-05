<?php

namespace SameOldNick\LaravelSuitcase\Tests\Unit\Commands;

use SameOldNick\LaravelSuitcase\Contracts\PackPipelineStep;
use SameOldNick\LaravelSuitcase\Runners\Steps\HandlesZipping;
use SameOldNick\LaravelSuitcase\Tests\TestCase;

/**
 * Unit tests for the `HandlesZipping` pipeline step.
 */
class HandlesZippingTest extends TestCase
{
    protected function createStep(): PackPipelineStep
    {
        return new HandlesZipping;
    }

    public function test_zip_package_archives_export_contents(): void
    {
        $config = $this->packConfig();
        $context = $this->createContext($config);
        $step = $this->createStep();

        $exportPath = $config->getExportPath();
        static::removeDirectory($exportPath);
        mkdir($exportPath.'/sub', 0777, true);
        file_put_contents($exportPath.'/INSTALL.txt', 'install');
        file_put_contents($exportPath.'/a.txt', 'a');
        file_put_contents($exportPath.'/sub/b.txt', 'b');

        $step->perform($context);

        $zipPath = $config->getZipPath();

        $this->assertFileExists($zipPath);

        $entries = $this->zipEntries($zipPath);
        $this->assertContains('INSTALL.txt', $entries);
        $this->assertContains('a.txt', $entries);
        $this->assertContains('sub/b.txt', $entries);
    }

    public function test_zip_package_overwrites_existing_archive(): void
    {
        $config = $this->packConfig();
        $context = $this->createContext($config);
        $step = $this->createStep();

        $exportPath = $config->getExportPath();
        static::removeDirectory($exportPath);
        mkdir($exportPath, 0777, true);
        file_put_contents($exportPath.'/stale.txt', 'stale');

        $step->perform($context);

        // Change the contents and zip again - the old entry should disappear.
        static::removeDirectory($exportPath);
        mkdir($exportPath, 0777, true);
        file_put_contents($exportPath.'/fresh.txt', 'fresh');

        $step->perform($context);

        $entries = $this->zipEntries($config->getZipPath());

        $this->assertContains('fresh.txt', $entries);
        $this->assertNotContains('stale.txt', $entries);
    }
}
