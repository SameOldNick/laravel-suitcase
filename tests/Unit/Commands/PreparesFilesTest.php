<?php

namespace SameOldNick\LaravelSuitcase\Tests\Unit\Commands;

use SameOldNick\LaravelSuitcase\Tests\TestCase;

/**
 * Unit tests for the `PreparesFiles` concern (env, constants, INSTALL.txt).
 */
class PreparesFilesTest extends TestCase
{
    public function test_normalize_env_value_handles_bool_true(): void
    {
        $command = $this->makePackCommand($this->packConfig());

        $this->assertSame('true', $this->invoke($command, 'normalizeEnvValue', [true]));
    }

    public function test_normalize_env_value_handles_bool_false(): void
    {
        $command = $this->makePackCommand($this->packConfig());

        $this->assertSame('false', $this->invoke($command, 'normalizeEnvValue', [false]));
    }

    public function test_normalize_env_value_joins_arrays(): void
    {
        $command = $this->makePackCommand($this->packConfig());

        $this->assertSame('a,b,c', $this->invoke($command, 'normalizeEnvValue', [['a', 'b', 'c']]));
    }

    public function test_normalize_env_value_strips_quotes_from_strings(): void
    {
        $command = $this->makePackCommand($this->packConfig());

        $this->assertSame('some quoted text', $this->invoke($command, 'normalizeEnvValue', ['some "quoted" text']));
        $this->assertSame('its', $this->invoke($command, 'normalizeEnvValue', ["it's"]));
    }

    public function test_normalize_env_value_handles_null_and_scalars(): void
    {
        $command = $this->makePackCommand($this->packConfig());

        $this->assertSame('null', $this->invoke($command, 'normalizeEnvValue', [null]));
        $this->assertSame('42', $this->invoke($command, 'normalizeEnvValue', [42]));
        $this->assertSame('1.5', $this->invoke($command, 'normalizeEnvValue', [1.5]));
    }

    public function test_set_env_variable_replaces_existing_key(): void
    {
        $command = $this->makePackCommand($this->packConfig());

        $contents = "APP_NAME=OldName\nSESSION_DRIVER=file\n";

        $result = $this->invoke($command, 'setEnvVariable', [$contents, 'APP_NAME', 'NewName']);

        $this->assertSame("APP_NAME=NewName\nSESSION_DRIVER=file\n", $result);
    }

    public function test_set_env_variable_appends_missing_key(): void
    {
        $command = $this->makePackCommand($this->packConfig());

        $contents = "APP_NAME=OldName\n";

        $result = $this->invoke($command, 'setEnvVariable', [$contents, 'NEW_KEY', 'value']);

        $this->assertStringContainsString('NEW_KEY=value', $result);
    }

    public function test_set_env_variable_does_not_touch_similar_keys(): void
    {
        $command = $this->makePackCommand($this->packConfig());

        $contents = "APP=root\nAPP_NAME=Old\n";

        $result = $this->invoke($command, 'setEnvVariable', [$contents, 'APP_NAME', 'New']);

        $this->assertSame("APP=root\nAPP_NAME=New\n", $result);
    }

    public function test_update_env_file_replaces_and_appends_variables(): void
    {
        $config = $this->packConfig();
        $command = $this->makePackCommand($config);

        $envPath = $this->app->basePath('unit-env.env');
        file_put_contents($envPath, "APP_NAME=OldName\nAPP_DEBUG=true\n");

        $this->invoke($command, 'updateEnvFile', [
            $envPath,
            [
                'APP_ENV' => 'production',
                'APP_DEBUG' => false,
                'SHARED_HOSTING' => true,
                'NEW_ARRAY' => ['a', 'b'],
            ],
        ]);

        $contents = file_get_contents($envPath);

        $this->assertStringContainsString('APP_NAME=OldName', $contents);
        $this->assertStringContainsString('APP_ENV=production', $contents);
        $this->assertStringContainsString('APP_DEBUG=false', $contents);
        $this->assertStringContainsString('SHARED_HOSTING=true', $contents);
        $this->assertStringContainsString('NEW_ARRAY=a,b', $contents);
    }

    public function test_update_constants_file_replaces_remote_path_placeholder(): void
    {
        config(['suitcase.remote.laravel_path' => '/srv/www/laravel']);

        $config = $this->packConfig();
        $command = $this->makePackCommand($config);

        $path = $this->app->basePath('constants-unit.php');
        file_put_contents($path, "<?php\ndefine('LARAVEL_PUBLIC_DIR', __DIR__);\ndefine('LARAVEL_ROOT_DIR', {{ laravelRootDir }});\n");

        $this->invoke($command, 'updateConstantsFile', [$path]);

        $this->assertStringContainsString("define('LARAVEL_ROOT_DIR', '/srv/www/laravel');", file_get_contents($path));
    }

    public function test_create_install_file_includes_database_instructions_when_dump_enabled(): void
    {
        $config = $this->packConfig(['db_dump.enabled' => true]);
        $command = $this->makePackCommand($config);

        $exportPath = $config->getExportPath();
        if (! is_dir($exportPath)) {
            mkdir($exportPath, 0777, true);
        }

        $this->invoke($command, 'createInstallFile');

        $install = file_get_contents($config->getExportPath().'/INSTALL.txt');

        $this->assertStringContainsString('import the database.sql file included in this package', $install);
        $this->assertStringContainsString('Create a MySQL database', $install);
        $this->assertStringContainsString('DB_DATABASE', $install);
        $this->assertStringContainsString('schedule:run', $install);
    }

    public function test_create_install_file_notes_missing_dump_when_disabled(): void
    {
        $config = $this->packConfig(['db_dump.enabled' => false]);
        $command = $this->makePackCommand($config);

        $exportPath = $config->getExportPath();
        if (! is_dir($exportPath)) {
            mkdir($exportPath, 0777, true);
        }

        $this->invoke($command, 'createInstallFile');

        $install = file_get_contents($config->getExportPath().'/INSTALL.txt');

        $this->assertStringContainsString('database dumping was disabled', $install);
        $this->assertStringNotContainsString('import the database.sql file included in this package', $install);
    }

    public function test_prepare_setup_requirements_writes_php_array_file(): void
    {
        $config = $this->packConfig();
        $command = $this->makePackCommand($config);

        $publicPath = $this->app->basePath('setup-public');
        mkdir($publicPath.'/setup', 0777, true);

        $this->invoke($command, 'prepareSetupRequirements', [$publicPath, ['php' => '8.2']]);

        $this->assertFileExists($publicPath.'/setup/requirements.php');
        $this->assertStringContainsString("'php' => '8.2'", file_get_contents($publicPath.'/setup/requirements.php'));
    }
}
