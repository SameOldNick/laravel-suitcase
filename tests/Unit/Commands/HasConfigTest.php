<?php

namespace SameOldNick\LaravelSuitcase\Tests\Unit\Commands;

use SameOldNick\LaravelSuitcase\Tests\TestCase;

/**
 * Unit tests for the `HasConfig` concern (config validation).
 */
class HasConfigTest extends TestCase
{
    public function test_set_and_get_config_roundtrip(): void
    {
        $config = $this->packConfig();
        $command = $this->makePackCommand($config);

        $this->assertSame($config, $this->invoke($command, 'getConfig'));
    }

    public function test_validate_config_returns_true_when_env_file_exists(): void
    {
        $this->givenSharedEnvFile();

        $command = $this->makePackCommand($this->packConfig());

        $this->assertTrue($this->invoke($command, 'validateConfig'));
    }

    public function test_validate_config_returns_false_and_reports_error_when_env_missing(): void
    {
        // Intentionally no .env.shared file (a previous test may have created one).
        @unlink($this->app->basePath('.env.shared'));

        $command = $this->makePackCommand($this->packConfig());

        $this->assertFalse($this->invoke($command, 'validateConfig'));

        $output = $this->commandOutput($command);
        $this->assertStringContainsString('Configuration error', $output);
        $this->assertStringContainsString('The specified .env file does not exist', $output);
    }

    public function test_validate_config_returns_false_when_env_file_configured_elsewhere(): void
    {
        $this->givenSharedEnvFile();

        // Point env_file at a path that does not exist.
        config(['suitcase.env_file' => 'missing/shared.env']);

        $command = $this->makePackCommand($this->packConfig());

        $this->assertFalse($this->invoke($command, 'validateConfig'));
        $this->assertStringContainsString('Configuration error', $this->commandOutput($command));
    }
}
