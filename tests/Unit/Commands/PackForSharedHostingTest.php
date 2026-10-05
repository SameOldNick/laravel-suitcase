<?php

namespace SameOldNick\LaravelSuitcase\Tests\Unit\Commands;

use SameOldNick\LaravelSuitcase\Tests\TestCase;

/**
 * Unit tests for the `suitcase:pack` command's configuration validation.
 */
class PackForSharedHostingTest extends TestCase
{
    public function test_validate_config_returns_true_when_env_file_exists(): void
    {
        $this->givenSharedEnvFile();

        $config = $this->packConfig();
        $command = $this->makePackCommand($config);

        $this->assertTrue($this->invoke($command, 'validateConfig', [$config]));
    }

    public function test_validate_config_returns_false_and_reports_error_when_env_missing(): void
    {
        // Intentionally no .env.shared file (a previous test may have created one).
        @unlink($this->app->basePath('.env.shared'));

        $config = $this->packConfig();
        $command = $this->makePackCommand($config);

        $this->assertFalse($this->invoke($command, 'validateConfig', [$config]));

        $output = $this->commandOutput($command);
        $this->assertStringContainsString('Configuration error', $output);
        $this->assertStringContainsString('The specified .env file does not exist', $output);
    }

    public function test_validate_config_returns_false_when_env_file_configured_elsewhere(): void
    {
        $this->givenSharedEnvFile();

        // Point env_file at a path that does not exist.
        config(['suitcase.env_file' => 'missing/shared.env']);

        $config = $this->packConfig();
        $command = $this->makePackCommand($config);

        $this->assertFalse($this->invoke($command, 'validateConfig', [$config]));
        $this->assertStringContainsString('Configuration error', $this->commandOutput($command));
    }
}
