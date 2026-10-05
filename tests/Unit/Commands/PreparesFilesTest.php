<?php

namespace SameOldNick\LaravelSuitcase\Tests\Unit\Commands;

use SameOldNick\LaravelSuitcase\Contracts\PackPipelineStep;
use SameOldNick\LaravelSuitcase\Runners\Steps\PreparesFiles;
use SameOldNick\LaravelSuitcase\Tests\TestCase;

/**
 * Unit tests for the `PreparesFiles` pipeline step (env, constants, INSTALL.txt).
 */
class PreparesFilesTest extends TestCase
{
    protected function createStep(): PackPipelineStep
    {
        $step = new PreparesFiles;

        return $step;
    }

    public function test_normalize_env_value_handles_bool_true(): void
    {
        $step = $this->createStep();

        $this->assertSame('true', $this->invoke($step, 'normalizeEnvValue', [true]));
    }

    public function test_normalize_env_value_handles_bool_false(): void
    {
        $step = $this->createStep();

        $this->assertSame('false', $this->invoke($step, 'normalizeEnvValue', [false]));
    }

    public function test_normalize_env_value_joins_arrays(): void
    {
        $step = $this->createStep();

        $this->assertSame('a,b,c', $this->invoke($step, 'normalizeEnvValue', [['a', 'b', 'c']]));
    }

    public function test_normalize_env_value_strips_quotes_from_strings(): void
    {
        $step = $this->createStep();

        $this->assertSame('some quoted text', $this->invoke($step, 'normalizeEnvValue', ['some "quoted" text']));
        $this->assertSame('its', $this->invoke($step, 'normalizeEnvValue', ["it's"]));
    }

    public function test_normalize_env_value_handles_null_and_scalars(): void
    {
        $step = $this->createStep();

        $this->assertSame('null', $this->invoke($step, 'normalizeEnvValue', [null]));
        $this->assertSame('42', $this->invoke($step, 'normalizeEnvValue', [42]));
        $this->assertSame('1.5', $this->invoke($step, 'normalizeEnvValue', [1.5]));
    }

    public function test_set_env_variable_replaces_existing_key(): void
    {
        $step = $this->createStep();

        $contents = "APP_NAME=OldName\nSESSION_DRIVER=file\n";

        $result = $this->invoke($step, 'setEnvVariable', [$contents, 'APP_NAME', 'NewName']);

        $this->assertSame("APP_NAME=NewName\nSESSION_DRIVER=file\n", $result);
    }

    public function test_set_env_variable_appends_missing_key(): void
    {
        $step = $this->createStep();

        $contents = "APP_NAME=OldName\n";

        $result = $this->invoke($step, 'setEnvVariable', [$contents, 'NEW_KEY', 'value']);

        $this->assertStringContainsString('NEW_KEY=value', $result);
    }

    public function test_set_env_variable_does_not_touch_similar_keys(): void
    {
        $step = $this->createStep();

        $contents = "APP=root\nAPP_NAME=Old\n";

        $result = $this->invoke($step, 'setEnvVariable', [$contents, 'APP_NAME', 'New']);

        $this->assertSame("APP=root\nAPP_NAME=New\n", $result);
    }

    public function test_update_env_file_replaces_and_appends_variables(): void
    {
        $context = $this->createContext($this->packConfig());
        $step = $this->createStep();

        $envPath = $this->app->basePath('unit-env.env');
        file_put_contents($envPath, "APP_NAME=OldName\nAPP_DEBUG=true\n");

        $this->invoke($step, 'updateEnvFile', [
            $context,
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

    public function test_update_constants_file_replaces_remote_path_placeholders(): void
    {
        config([
            'suitcase.remote.public_path' => '/srv/www/public_html',
            'suitcase.remote.laravel_path' => '/srv/www/laravel',
        ]);

        $context = $this->createContext($this->packConfig());
        $step = $this->createStep();

        $path = $this->app->basePath('constants-unit.php');
        file_put_contents($path, "<?php\ndefine('LARAVEL_PUBLIC_DIR', {{ laravelPublicDir }});\ndefine('LARAVEL_ROOT_DIR', {{ laravelRootDir }});\n");

        $this->invoke($step, 'updateConstantsFile', [$context, $path]);

        $contents = file_get_contents($path);

        $this->assertStringContainsString("define('LARAVEL_PUBLIC_DIR', '/srv/www/public_html');", $contents);
        $this->assertStringContainsString("define('LARAVEL_ROOT_DIR', '/srv/www/laravel');", $contents);
    }

    public function test_create_install_file_includes_database_instructions_when_dump_enabled(): void
    {
        $config = $this->packConfig(['db_dump.enabled' => true]);
        $context = $this->createContext($config);
        $step = $this->createStep();

        $exportPath = $config->getExportPath();
        if (! is_dir($exportPath)) {
            mkdir($exportPath, 0777, true);
        }

        $this->invoke($step, 'createInstallFile', [$context]);

        $install = file_get_contents($config->getExportPath().'/INSTALL.txt');

        $this->assertStringContainsString('import the database.sql file included in this package', $install);
        $this->assertStringContainsString('Create a MySQL database', $install);
        $this->assertStringContainsString('DB_DATABASE', $install);
        $this->assertStringContainsString('schedule:run', $install);
    }

    public function test_create_install_file_notes_missing_dump_when_disabled(): void
    {
        $config = $this->packConfig(['db_dump.enabled' => false]);
        $context = $this->createContext($config);
        $step = $this->createStep();

        $exportPath = $config->getExportPath();
        if (! is_dir($exportPath)) {
            mkdir($exportPath, 0777, true);
        }

        $this->invoke($step, 'createInstallFile', [$context]);

        $install = file_get_contents($config->getExportPath().'/INSTALL.txt');

        $this->assertStringContainsString('database dumping was disabled', $install);
        $this->assertStringNotContainsString('import the database.sql file included in this package', $install);
    }

    public function test_invoke_updates_constants_and_env_and_creates_install_file(): void
    {
        $config = $this->packConfig();
        $context = $this->createContext($config);
        $step = $this->createStep();

        $publicPath = $config->getPublicPath();
        $laravelPath = $config->getLaravelPath();

        if (! is_dir($publicPath)) {
            mkdir($publicPath, 0777, true);
        }

        if (! is_dir($laravelPath)) {
            mkdir($laravelPath, 0777, true);
        }

        file_put_contents($publicPath.'/constants.php', "<?php\ndefine('LARAVEL_PUBLIC_DIR', {{ laravelPublicDir }});\n");
        file_put_contents($laravelPath.'/.env', "APP_NAME=OldName\n");

        $step->perform($context);

        $this->assertFileExists($config->getExportPath().'/INSTALL.txt');
        $this->assertStringContainsString(
            "define('LARAVEL_PUBLIC_DIR', '/home/username/public_html');",
            file_get_contents($publicPath.'/constants.php')
        );

        $env = file_get_contents($laravelPath.'/.env');
        $this->assertStringContainsString('APP_NAME=OldName', $env);
        $this->assertStringContainsString('APP_ENV=production', $env);
    }
}
