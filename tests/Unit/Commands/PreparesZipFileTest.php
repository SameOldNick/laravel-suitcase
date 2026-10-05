<?php

namespace SameOldNick\LaravelSuitcase\Tests\Unit\Commands;

use Illuminate\Support\Facades\File;
use SameOldNick\LaravelSuitcase\Exceptions\ZipFileNotWritable;
use SameOldNick\LaravelSuitcase\Runners\Steps\PreparesZipFile;
use SameOldNick\LaravelSuitcase\Tests\TestCase;

/**
 * Unit tests for the `PreparesZipFile` pipeline step.
 */
class PreparesZipFileTest extends TestCase
{
    public function test_creates_zip_file_when_missing(): void
    {
        $config = $this->packConfig();
        @unlink($config->getZipPath());

        $context = $this->createContext($config);

        (new PreparesZipFile)($context);

        $this->assertFileExists($config->getZipPath());
    }

    public function test_throws_when_zip_file_is_not_writable(): void
    {
        File::shouldReceive('put')->once()->andReturn(false);

        $config = $this->packConfig();
        $context = $this->createContext($config);

        $this->expectException(ZipFileNotWritable::class);
        $this->expectExceptionMessage('The ZIP file is not writable');

        (new PreparesZipFile)($context);
    }
}
