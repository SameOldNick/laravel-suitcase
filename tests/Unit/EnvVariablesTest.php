<?php

namespace SameOldNick\LaravelSuitcase\Tests\Unit;

use SameOldNick\LaravelSuitcase\Support\EnvVariables;
use SameOldNick\LaravelSuitcase\Tests\TestCase;

class EnvVariablesTest extends TestCase
{
    public function test_default_variables_include_shared_hosting(): void
    {
        $variables = (new EnvVariables)->getDefaultVariables();

        $this->assertSame('true', $variables['SHARED_HOSTING']);
        $this->assertSame('production', $variables['APP_ENV']);
        $this->assertSame('false', $variables['APP_DEBUG']);
    }

    public function test_app_key_is_a_valid_base64_encrypted_key(): void
    {
        $variables = (new EnvVariables)->getDefaultVariables();

        $this->assertStringStartsWith('base64:', $variables['APP_KEY']);

        $key = base64_decode(substr($variables['APP_KEY'], strlen('base64:')), true);

        $this->assertNotFalse($key, 'APP_KEY should be valid base64.');
        $this->assertSame(32, strlen($key), 'AES-256-CBC keys are 32 bytes.');
    }

    public function test_customized_variables_dispatch_and_return_defaults(): void
    {
        $variables = (new EnvVariables)->getCustomizedVariables();

        $this->assertSame('true', $variables['SHARED_HOSTING']);
        $this->assertSame('production', $variables['APP_ENV']);
        $this->assertArrayHasKey('APP_KEY', $variables);
    }
}
