<?php

namespace SameOldNick\LaravelSuitcase\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Testing\PendingCommand;
use SameOldNick\LaravelSuitcase\Tests\TestCase;

/**
 * Feature tests for choosing which configuration file `suitcase:pack` uses.
 *
 * Supported invocation forms:
 *   php artisan suitcase:pack --config=config/suitcase.production.php
 *   php artisan suitcase:pack --config=production          # short for config/suitcase.production.php
 *   SUITCASE_CONFIG=config/suitcase.staging.php php artisan suitcase:pack
 *   php artisan suitcase:pack                              # config/suitcase.php over packaged defaults
 */
class PackConfigPathTest extends TestCase
{
    /**
     * Files created by a test that must not leak into the next one.
     *
     * @var array<int, string>
     */
    protected array $createdFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        // The test environment has no MySQL server; isolate the file-packaging pipeline.
        config(['suitcase.db_dump.enabled' => false]);

        $this->cleanupArtifacts();
    }

    protected function tearDown(): void
    {
        $this->cleanupArtifacts();
        $this->clearConfigEnvironment();

        parent::tearDown();
    }

    // ------------------------------------------------------------------
    // Selection via the --config flag
    // ------------------------------------------------------------------

    public function test_config_flag_selects_relative_file_resolved_against_base_path(): void
    {
        $this->givenMinimalLaravelApp();
        $this->givenSharedEnvFile();

        $path = $this->writeConfigFile(
            'config/suitcase.production.php',
            $this->fullConfig('flag-pack.zip', '/srv/flag-laravel')
        );

        $this->assertConfigPathsChecked(
            $this->artisan('suitcase:pack', ['--config' => 'config/suitcase.production.php']),
            found: [$path],
            missing: [
                config_path('suitcase.php'),
                'config/suitcase.production.php',
                config_path('suitcase.config/suitcase.production.php.php'),
            ],
        )
            ->expectsConfirmation('Do you want to continue?', 'yes')
            ->assertExitCode(0);

        $zip = $this->app->basePath('flag-pack.zip');
        $this->assertFileExists($zip, 'The file named by the selected config should be produced.');
        $this->assertStringContainsString(
            '/srv/flag-laravel',
            $this->zipEntryContents($zip, 'public_html/constants.php')
        );
    }

    public function test_config_flag_accepts_bare_name_shorthand(): void
    {
        $this->givenMinimalLaravelApp();
        $this->givenSharedEnvFile();

        $this->writeConfigFile(
            'config/suitcase.production.php',
            $this->fullConfig('bare-pack.zip', '/srv/bare-laravel')
        );

        $this->assertConfigPathsChecked(
            $this->artisan('suitcase:pack', ['--config' => 'production']),
            found: [config_path('suitcase.production.php')],
            missing: [
                config_path('suitcase.php'),
                'production',
                base_path('production'),
            ],
        )
            ->expectsConfirmation('Do you want to continue?', 'yes')
            ->assertExitCode(0);

        $zip = $this->app->basePath('bare-pack.zip');
        $this->assertFileExists($zip, '`--config=production` should resolve to config/suitcase.production.php.');
        $this->assertStringContainsString(
            '/srv/bare-laravel',
            $this->zipEntryContents($zip, 'public_html/constants.php')
        );
    }

    public function test_config_flag_accepts_absolute_path_unchanged(): void
    {
        $this->givenMinimalLaravelApp();
        $this->givenSharedEnvFile();

        $absolute = $this->writeConfigFile(
            'absolute-suitcase.php',
            $this->fullConfig('abs-pack.zip', '/srv/abs-laravel')
        );

        $this->assertConfigPathsChecked(
            $this->artisan('suitcase:pack', ['--config' => $absolute]),
            found: [$absolute],
            missing: [
                config_path('suitcase.php'),
                base_path($absolute),
                config_path('suitcase.'.$absolute.'.php'),
            ],
        )
            ->expectsConfirmation('Do you want to continue?', 'yes')
            ->assertExitCode(0);

        $zip = $this->app->basePath('abs-pack.zip');
        $this->assertFileExists($zip, 'An absolute path should be used unchanged.');
        $this->assertStringContainsString(
            '/srv/abs-laravel',
            $this->zipEntryContents($zip, 'public_html/constants.php')
        );
    }

    public function test_config_value_with_php_suffix_resolves_against_base_path(): void
    {
        $this->givenMinimalLaravelApp();
        $this->givenSharedEnvFile();

        $path = $this->writeConfigFile(
            'suitcase.standalone.php',
            $this->fullConfig('suffix-pack.zip', '/srv/suffix-laravel')
        );

        $this->assertConfigPathsChecked(
            $this->artisan('suitcase:pack', ['--config' => 'suitcase.standalone.php']),
            found: [$path],
            missing: [
                config_path('suitcase.php'),
                'suitcase.standalone.php',
                config_path('suitcase.suitcase.standalone.php.php'),
            ],
        )
            ->expectsConfirmation('Do you want to continue?', 'yes')
            ->assertExitCode(0);

        $this->assertFileExists($this->app->basePath('suffix-pack.zip'));
    }

    // ------------------------------------------------------------------
    // Selection via the SUITCASE_CONFIG environment variable
    // ------------------------------------------------------------------

    public function test_environment_variable_selects_config_file(): void
    {
        $this->givenMinimalLaravelApp();
        $this->givenSharedEnvFile();

        $path = $this->writeConfigFile(
            'config/suitcase.staging.php',
            $this->fullConfig('env-pack.zip', '/srv/env-laravel')
        );

        // Present but unreferenced: it must not be reported as checked.
        $this->writeConfigFile(
            'config/suitcase.production.php',
            $this->fullConfig('unused-pack.zip', '/srv/unused-laravel')
        );

        $this->useConfigEnvironment('config/suitcase.staging.php');

        $this->assertConfigPathsChecked(
            $this->artisan('suitcase:pack'),
            found: [$path],
            missing: [
                config_path('suitcase.php'),
                'config/suitcase.staging.php',
                config_path('suitcase.config/suitcase.staging.php.php'),
            ],
            notChecked: ['suitcase.production.php'],
        )
            ->expectsConfirmation('Do you want to continue?', 'yes')
            ->assertExitCode(0);

        $zip = $this->app->basePath('env-pack.zip');
        $this->assertFileExists($zip, 'SUITCASE_CONFIG should select config/suitcase.staging.php.');
        $this->assertStringContainsString(
            '/srv/env-laravel',
            $this->zipEntryContents($zip, 'public_html/constants.php')
        );
    }

    public function test_config_flag_takes_precedence_over_environment_variable(): void
    {
        $this->givenMinimalLaravelApp();
        $this->givenSharedEnvFile();

        $this->writeConfigFile(
            'config/suitcase.flag.php',
            $this->fullConfig('flag-wins.zip', '/srv/flag-wins-laravel')
        );
        $this->writeConfigFile(
            'config/suitcase.env.php',
            $this->fullConfig('env-loses.zip', '/srv/env-loses-laravel')
        );

        $this->useConfigEnvironment('config/suitcase.env.php');

        $this->assertConfigPathsChecked(
            $this->artisan('suitcase:pack', ['--config' => 'config/suitcase.flag.php']),
            found: [base_path('config/suitcase.flag.php')],
            missing: [
                config_path('suitcase.php'),
                'config/suitcase.flag.php',
                config_path('suitcase.config/suitcase.flag.php.php'),
            ],
            notChecked: ['suitcase.env.php'],
        )
            ->expectsConfirmation('Do you want to continue?', 'yes')
            ->assertExitCode(0);

        $this->assertFileExists($this->app->basePath('flag-wins.zip'), 'The flag file must win.');
        $this->assertFileDoesNotExist($this->app->basePath('env-loses.zip'), 'The env file must be ignored.');

        $constants = $this->zipEntryContents($this->app->basePath('flag-wins.zip'), 'public_html/constants.php');
        $this->assertStringContainsString('/srv/flag-wins-laravel', $constants);
        $this->assertStringNotContainsString('/srv/env-loses-laravel', $constants);
    }

    public function test_missing_environment_config_file_fails_before_any_export_work(): void
    {
        $this->givenMinimalLaravelApp();
        $this->givenSharedEnvFile();

        $this->useConfigEnvironment('config/suitcase.missing.php');

        $resolved = $this->absolutePath($this->app->basePath('config/suitcase.missing.php'));

        [$exitCode, $output] = $this->runPackCommand();

        $this->assertSame(1, $exitCode, 'A missing SUITCASE_CONFIG file must fail with a non-zero status.');
        $this->assertStringContainsString($resolved, $output, 'The error must name the resolved absolute path.');

        $this->assertFileDoesNotExist($this->app->basePath('laravel_shared_hosting_pack.zip'));
        $this->assertDirectoryDoesNotExist($this->app->basePath('deploy'));
    }

    public function test_missing_flag_config_does_not_fall_back_to_environment_variable(): void
    {
        $this->givenMinimalLaravelApp();
        $this->givenSharedEnvFile();

        $this->writeConfigFile(
            'config/suitcase.env.php',
            $this->fullConfig('env-fallback.zip', '/srv/env-fallback-laravel')
        );

        $this->useConfigEnvironment('config/suitcase.env.php');

        [$exitCode, $output] = $this->runPackCommand(['--config' => 'config/suitcase.flag-missing.php']);

        $this->assertSame(1, $exitCode, 'The flag must win, so a missing flag file is fatal.');
        $this->assertStringContainsString($this->absolutePath($this->app->basePath('config/suitcase.flag-missing.php')), $output);

        $this->assertFileDoesNotExist($this->app->basePath('env-fallback.zip'));
        $this->assertDirectoryDoesNotExist($this->app->basePath('deploy'));
    }

    // ------------------------------------------------------------------
    // Defaults and merging
    // ------------------------------------------------------------------

    public function test_defaults_are_used_when_no_config_is_specified(): void
    {
        $this->givenMinimalLaravelApp();
        $this->givenSharedEnvFile();

        // Present but unreferenced: it must not be reported as checked.
        $this->writeConfigFile(
            'config/suitcase.production.php',
            $this->fullConfig('unused-pack.zip', '/srv/unused-laravel')
        );

        $this->assertConfigPathsChecked(
            $this->artisan('suitcase:pack'),
            found: [],
            missing: [config_path('suitcase.php')],
            notChecked: ['suitcase.production.php'],
        )
            ->expectsConfirmation('Do you want to continue?', 'yes')
            ->assertExitCode(0);

        $zip = $this->app->basePath('laravel_shared_hosting_pack.zip');
        $this->assertFileExists($zip, 'Without a selection the packaged defaults must still be used.');
        $this->assertStringContainsString(
            '/home/username/laravel',
            $this->zipEntryContents($zip, 'public_html/constants.php')
        );
    }

    public function test_empty_config_value_falls_back_to_defaults(): void
    {
        $this->givenMinimalLaravelApp();
        $this->givenSharedEnvFile();

        $this->assertConfigPathsChecked(
            $this->artisan('suitcase:pack', ['--config' => '']),
            found: [],
            missing: [config_path('suitcase.php')],
        )
            ->expectsConfirmation('Do you want to continue?', 'yes')
            ->assertExitCode(0);

        $this->assertFileExists($this->app->basePath('laravel_shared_hosting_pack.zip'));
    }

    public function test_default_config_file_in_use_is_reported(): void
    {
        $this->writeConfigFile('config/suitcase.php', [
            'remote' => ['laravel_path' => '/srv/default-laravel'],
        ]);

        $this->refreshApplication();
        config(['suitcase.db_dump.enabled' => false]);

        $this->givenMinimalLaravelApp();
        $this->givenSharedEnvFile();

        $this->assertConfigPathsChecked(
            $this->artisan('suitcase:pack'),
            found: [config_path('suitcase.php')],
            missing: [],
        )
            ->expectsConfirmation('Do you want to continue?', 'yes')
            ->assertExitCode(0);
    }

    public function test_default_config_file_is_merged_over_packaged_defaults(): void
    {
        // A published config/suitcase.php only needs to override the keys it cares about.
        $this->writeConfigFile('config/suitcase.php', [
            'remote' => ['laravel_path' => '/srv/default-laravel'],
        ]);

        // Recreate the application so the freshly written config file is loaded,
        // exactly as a published config would be on a real `php artisan` run.
        $this->refreshApplication();
        config(['suitcase.db_dump.enabled' => false]);

        $this->givenMinimalLaravelApp();
        $this->givenSharedEnvFile();

        $this->artisan('suitcase:pack')
            ->expectsConfirmation('Do you want to continue?', 'yes')
            ->assertExitCode(0);

        $zip = $this->app->basePath('laravel_shared_hosting_pack.zip');
        $this->assertFileExists($zip);

        $constants = $this->zipEntryContents($zip, 'public_html/constants.php');
        $this->assertStringContainsString('/srv/default-laravel', $constants, 'The app config must win.');
        $this->assertStringContainsString('/home/username/public_html', $constants, 'Omitted keys keep packaged defaults.');
    }

    public function test_partial_config_file_is_merged_over_packaged_defaults(): void
    {
        $path = $this->writeConfigFile('config/suitcase.partial.php', [
            'remote' => ['laravel_path' => '/srv/partial-laravel'],
        ]);

        $command = $this->makePackCommand(['--config' => $path]);
        $config = $this->invoke($command, 'createConfig');

        // The provided key wins...
        $this->assertSame('/srv/partial-laravel', $config->getRemoteLaravelPath());

        // ...while every omitted key falls back to the packaged defaults.
        $this->assertSame('/home/username/public_html', $config->getRemotePublicPath());
        $this->assertSame(base_path('deploy'), $config->getExportPath());
        $this->assertSame(base_path('laravel_shared_hosting_pack.zip'), $config->getZipPath());
        $this->assertSame(base_path('.env.shared'), $config->getEnvFilePath());
        $this->assertSame($this->packagedFileSelection()['include']['laravel'], $config->getLaravelIncludes());
        $this->assertSame(array_merge($this->packagedFileSelection()['exclude']['laravel'], ['config/suitcase.partial.php']), $config->getLaravelExcludes());
    }

    public function test_partial_alternate_config_packs_with_overridden_remote_values(): void
    {
        $this->givenMinimalLaravelApp();
        $this->givenSharedEnvFile();

        // Only the remote path is set; export_dir/zip_name/env_file come from
        // the packaged defaults, so the file must still be packable.
        $this->writeConfigFile('config/suitcase.partial.php', [
            'remote' => ['laravel_path' => '/srv/partial-laravel'],
        ]);

        $this->artisan('suitcase:pack', ['--config' => 'config/suitcase.partial.php'])
            ->expectsConfirmation('Do you want to continue?', 'yes')
            ->assertExitCode(0);

        $zip = $this->app->basePath('laravel_shared_hosting_pack.zip');
        $this->assertFileExists($zip, 'A partial config must still pack.');

        $constants = $this->zipEntryContents($zip, 'public_html/constants.php');
        $this->assertStringContainsString('/srv/partial-laravel', $constants);
        $this->assertStringContainsString('/home/username/public_html', $constants, 'Omitted keys keep packaged defaults.');

        $this->assertStringContainsString(
            '/srv/partial-laravel',
            $this->zipEntryContents($zip, 'INSTALL.txt')
        );
    }

    public function test_list_valued_options_are_replaced_not_appended(): void
    {
        $path = $this->writeConfigFile('config/suitcase.lists.php', [
            'include' => ['laravel' => ['app/*']],
            'exclude' => ['laravel' => ['custom/*']],
        ]);

        $command = $this->makePackCommand(['--config' => $path]);
        $config = $this->invoke($command, 'createConfig');

        $this->assertSame(['app/*'], $config->getLaravelIncludes());
        $this->assertSame(['custom/*', 'config/suitcase.lists.php'], $config->getLaravelExcludes());
        $this->assertNotContains('deploy/*', $config->getLaravelExcludes(), 'Packaged list entries must not be appended.');
    }

    // ------------------------------------------------------------------
    // Failure modes
    // ------------------------------------------------------------------

    public function test_config_value_pointing_at_a_directory_fails_before_any_export_work(): void
    {
        $this->givenMinimalLaravelApp();
        $this->givenSharedEnvFile();

        $directory = $this->writeConfigDirectory('config/suitcase.dir.php');

        [$exitCode, $output] = $this->runPackCommand(['--config' => 'config/suitcase.dir.php']);

        $this->assertSame(1, $exitCode, 'A directory in place of a config file must fail with a non-zero status.');
        $this->assertStringContainsString($this->absolutePath($directory), $output);

        $this->assertFileDoesNotExist($this->app->basePath('laravel_shared_hosting_pack.zip'));
        $this->assertDirectoryDoesNotExist($this->app->basePath('deploy'));
    }

    public function test_missing_config_file_fails_before_any_export_work(): void
    {
        $this->givenMinimalLaravelApp();
        $this->givenSharedEnvFile();

        $resolved = $this->absolutePath($this->app->basePath('config/suitcase.missing.php'));

        [$exitCode, $output] = $this->runPackCommand(['--config' => 'config/suitcase.missing.php']);

        $this->assertSame(1, $exitCode, 'A missing config file must fail with a non-zero status.');
        $this->assertStringContainsString($resolved, $output, 'The error must name the resolved absolute path.');

        $this->assertFileDoesNotExist($this->app->basePath('laravel_shared_hosting_pack.zip'));
        $this->assertDirectoryDoesNotExist($this->app->basePath('deploy'));
    }

    public function test_non_array_config_file_fails_before_any_export_work(): void
    {
        $this->givenMinimalLaravelApp();
        $this->givenSharedEnvFile();

        $this->writeRawConfigFile('config/suitcase.broken.php', "<?php\n\nreturn 'not an array';\n");

        $resolved = $this->absolutePath($this->app->basePath('config/suitcase.broken.php'));

        [$exitCode, $output] = $this->runPackCommand(['--config' => 'config/suitcase.broken.php']);

        $this->assertSame(1, $exitCode, 'A non-array config file must fail with a non-zero status.');
        $this->assertStringContainsString($resolved, $output);

        $this->assertFileDoesNotExist($this->app->basePath('laravel_shared_hosting_pack.zip'));
        $this->assertDirectoryDoesNotExist($this->app->basePath('deploy'));
    }

    public function test_unreadable_config_file_fails_before_any_export_work(): void
    {
        $this->givenMinimalLaravelApp();
        $this->givenSharedEnvFile();

        $path = $this->writeConfigFile(
            'config/suitcase.unreadable.php',
            $this->fullConfig('unreadable-pack.zip', '/srv/unreadable-laravel')
        );

        @chmod($path, 0000);

        if (is_readable($path)) {
            $this->markTestSkipped('Cannot simulate an unreadable file in this environment.');
        }

        [$exitCode, $output] = $this->runPackCommand(['--config' => 'config/suitcase.unreadable.php']);

        $this->assertSame(1, $exitCode, 'An unreadable config file must fail with a non-zero status.');
        $this->assertStringContainsString($this->absolutePath($path), $output);

        $this->assertFileDoesNotExist($this->app->basePath('unreadable-pack.zip'));
        $this->assertDirectoryDoesNotExist($this->app->basePath('deploy'));
    }

    // ------------------------------------------------------------------
    // The selected file must not ship with the package
    // ------------------------------------------------------------------

    public function test_selected_config_file_is_not_copied_into_export_or_zip(): void
    {
        $this->givenMinimalLaravelApp();
        $this->givenSharedEnvFile();

        $this->writeConfigFile(
            'config/suitcase.production.php',
            $this->fullConfig('excluded-pack.zip', '/srv/excluded-laravel')
        );

        $this->artisan('suitcase:pack', ['--config' => 'config/suitcase.production.php'])
            ->expectsConfirmation('Do you want to continue?', 'yes')
            ->assertExitCode(0);

        $zip = $this->app->basePath('excluded-pack.zip');
        $this->assertFileExists($zip);

        $entries = $this->zipEntries($zip);

        // Sanity check: the rest of the config directory is exported...
        $this->assertContains('laravel/config/custom.php', $entries);

        // ...but never the file that selected the configuration.
        $this->assertNotContains('laravel/config/suitcase.production.php', $entries);
        $this->assertFileDoesNotExist($this->app->basePath('deploy/laravel/config/suitcase.production.php'));
    }

    public function test_default_config_file_is_not_copied_into_export_or_zip(): void
    {
        $this->writeConfigFile('config/suitcase.php', [
            'remote' => ['laravel_path' => '/srv/default-laravel'],
        ]);

        $this->refreshApplication();
        config(['suitcase.db_dump.enabled' => false]);

        $this->givenMinimalLaravelApp();
        $this->givenSharedEnvFile();

        $this->artisan('suitcase:pack')
            ->expectsConfirmation('Do you want to continue?', 'yes')
            ->assertExitCode(0);

        $zip = $this->app->basePath('laravel_shared_hosting_pack.zip');
        $entries = $this->zipEntries($zip);

        $this->assertContains('laravel/config/custom.php', $entries);
        $this->assertNotContains('laravel/config/suitcase.php', $entries);
        $this->assertFileDoesNotExist($this->app->basePath('deploy/laravel/config/suitcase.php'));
    }

    public function test_skip_env_flag_is_honored_with_alternate_config(): void
    {
        $this->givenMinimalLaravelApp();
        $this->givenSharedEnvFile();

        $this->writeConfigFile(
            'config/suitcase.production.php',
            $this->fullConfig('skip-pack.zip', '/srv/skip-laravel')
        );

        $this->artisan('suitcase:pack', [
            '--config' => 'config/suitcase.production.php',
            '--skip-env' => true,
        ])
            ->expectsConfirmation('Do you want to continue?', 'yes')
            ->assertExitCode(0);

        $this->assertNotContains('laravel/.env', $this->zipEntries($this->app->basePath('skip-pack.zip')));
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * A complete-enough config file used by the selection tests.
     *
     * @return array<string, mixed>
     */
    protected function fullConfig(string $zipName, string $remoteLaravel, string $remotePublic = '/srv/flag-public'): array
    {
        return array_merge([
            'export_dir' => 'deploy',
            'zip_name' => $zipName,
            'env_file' => '.env.shared',
            'db_dump' => ['enabled' => false],
            'remote' => [
                'laravel_path' => $remoteLaravel,
                'public_path' => $remotePublic,
            ],
        ], $this->packagedFileSelection());
    }

    /**
     * The packaged include/exclude lists.
     *
     * A custom config file that omits these would otherwise cause the exporter
     * to walk the destination directory it is still writing into.
     *
     * @return array<string, mixed>
     */
    protected function packagedFileSelection(): array
    {
        return [
            'include' => [
                'laravel' => [
                    'app/*',
                    'bootstrap/*',
                    'config/*',
                    'database/*',
                    'lang/*',
                    'resources/*',
                    'routes/*',
                    'storage/*',
                    'vendor/*',
                    'artisan',
                    'composer.json',
                    'composer.lock',
                ],
                'public' => ['*'],
            ],
            'exclude' => [
                'laravel' => [
                    'laravel_shared_hosting_pack.zip',
                    '.env',
                    'public/*',
                    'node_modules/*',
                    '.yarn/*',
                    'tests/*',
                    '.git/*',
                    'deploy/*',
                    'storage/logs/*',
                    'storage/framework/cache/*',
                    'storage/framework/sessions/*',
                    'storage/framework/testing/*',
                    'storage/framework/views/*',
                ],
                'public' => [
                    'hot',
                    'setup/*',
                    'storage/*',
                    '.gitignore',
                    '.gitattributes',
                ],
            ],
        ];
    }

    /**
     * Write a PHP config file returning the given array, relative to the app base path.
     *
     * @param  array<string, mixed>  $config
     * @return string The absolute path to the written file.
     */
    protected function writeConfigFile(string $relativePath, array $config): string
    {
        return $this->writeRawConfigFile(
            $relativePath,
            "<?php\n\nreturn ".var_export($config, true).";\n"
        );
    }

    /**
     * Write literal PHP source as a config file, relative to the app base path.
     *
     * @return string The absolute path to the written file.
     */
    protected function writeRawConfigFile(string $relativePath, string $contents): string
    {
        $path = $this->app->basePath($relativePath);

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        file_put_contents($path, $contents);
        $this->createdFiles[] = $path;

        return $path;
    }

    /**
     * Create a directory that occupies a config file path.
     *
     * @return string The absolute path to the created directory.
     */
    protected function writeConfigDirectory(string $relativePath): string
    {
        $path = $this->app->basePath($relativePath);

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        mkdir($path, 0777, true);
        $this->createdFiles[] = $path;

        return $path;
    }

    /**
     * The absolute path the command is expected to report for a given file,
     * applying the same normalisation the command uses in its error messages.
     */
    protected function absolutePath(string $path): string
    {
        $path = realpath($path) ?: $path;

        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    }

    /**
     * Expose SUITCASE_CONFIG to the packaged config exactly like a fresh
     * `php artisan` process would, then rebuild the application so the value
     * is picked up during configuration loading.
     */
    protected function useConfigEnvironment(string $value): void
    {
        putenv("SUITCASE_CONFIG={$value}");
        $_ENV['SUITCASE_CONFIG'] = $value;
        $_SERVER['SUITCASE_CONFIG'] = $value;

        $this->refreshApplication();
        config(['suitcase.db_dump.enabled' => false]);
    }

    protected function clearConfigEnvironment(): void
    {
        putenv('SUITCASE_CONFIG');
        unset($_ENV['SUITCASE_CONFIG'], $_SERVER['SUITCASE_CONFIG']);
    }

    /**
     * Run the pack command through the real console instead of the mocked
     * `PendingCommand` output.
     *
     * The failure-mode tests exercise a command that must be free to abort
     * before the interactive confirmation prompt; the mocked output throws on
     * any unexpected question, so these tests use a real buffered output and
     * run non-interactively.
     *
     * @param  array<string, mixed>  $parameters
     * @return array{0: int, 1: string} The exit code and captured output.
     */
    protected function runPackCommand(array $parameters = []): array
    {
        $exitCode = Artisan::call('suitcase:pack', $parameters + ['--no-interaction' => true]);

        return [$exitCode, Artisan::output()];
    }

    /**
     * Assert which config path(s) the command reported as checked.
     *
     * Paths in $found must appear in the "Config file paths checked" banner
     * with a ✅ marker; paths in $missing must appear with a ❌ marker; paths
     * in $notChecked must be absent from the whole output, proving they were
     * never consulted.
     *
     * @param  array<int, string>  $found  Paths expected to be reported as present.
     * @param  array<int, string>  $missing  Paths expected to be reported as absent.
     * @param  array<int, string>  $notChecked  Paths that must not be reported or consulted.
     */
    protected function assertConfigPathsChecked(PendingCommand $command, array $found, array $missing = [], array $notChecked = []): PendingCommand
    {
        $command->expectsOutputToContain('Config file paths checked:');

        foreach ($found as $path) {
            $command->expectsOutputToContain('✅ '.$path);
        }

        foreach ($missing as $path) {
            $command->expectsOutputToContain('❌ '.$path);
        }

        foreach ($notChecked as $path) {
            $command->doesntExpectOutputToContain($path);
        }

        return $command;
    }

    /**
     * Remove config fixtures and packaging output between tests.
     */
    protected function cleanupArtifacts(): void
    {
        $base = $this->app->basePath();

        foreach ($this->createdFiles as $file) {
            if (is_dir($file)) {
                static::removeDirectory($file);
            } elseif (is_file($file)) {
                @chmod($file, 0666);
                @unlink($file);
            }
        }

        $this->createdFiles = [];

        $configDir = $base.DIRECTORY_SEPARATOR.'config';
        if (is_dir($configDir)) {
            foreach (scandir($configDir) ?: [] as $entry) {
                if (str_starts_with($entry, 'suitcase') && str_ends_with($entry, '.php')) {
                    $file = $configDir.DIRECTORY_SEPARATOR.$entry;

                    if (is_dir($file)) {
                        static::removeDirectory($file);
                    } else {
                        @chmod($file, 0666);
                        @unlink($file);
                    }
                }
            }
        }

        foreach (scandir($base) ?: [] as $entry) {
            if (str_ends_with($entry, '.zip')) {
                @unlink($base.DIRECTORY_SEPARATOR.$entry);
            }
        }

        @unlink($base.DIRECTORY_SEPARATOR.'.env.shared');

        static::removeDirectory($base.DIRECTORY_SEPARATOR.'deploy');
    }
}
