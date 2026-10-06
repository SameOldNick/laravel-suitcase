<?php

namespace SameOldNick\LaravelSuitcase\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use SameOldNick\LaravelSuitcase\Config\Options;
use SameOldNick\LaravelSuitcase\Config\PackConfig;
use SameOldNick\LaravelSuitcase\Contracts\EnvVariables;
use SameOldNick\LaravelSuitcase\Contracts\Outputter;
use SameOldNick\LaravelSuitcase\Runners\PackForSharedHostingRunner;
use SameOldNick\LaravelSuitcase\Support\EventDispatcher;
use SameOldNick\LaravelSuitcase\Support\Outputters\ConsoleOutputter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to package a Laravel application for deployment on shared hosting.
 */
class PackForSharedHosting extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'suitcase:pack
                            {--config= : Path to the configuration file (optional)}
                            {--skip-vendor : Skip vendor directory}
                            {--skip-env : Skip .env file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Package Laravel app for deployment to shared hosting (e.g., cPanel)';

    /**
     * Map the console skip options onto the package configuration.
     *
     * The pipeline steps read these values from `PackConfig`, so the options
     * must be applied before the config (and its `Options` value object) is
     * resolved for `handle()`.
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);

        // Only override the config defaults when a flag is actually passed, so
        // the SKIP_ENV / SKIP_VENDOR environment defaults are preserved.
        if ($this->option('skip-env')) {
            config(['suitcase.skip.env' => true]);
        }

        if ($this->option('skip-vendor')) {
            config(['suitcase.skip.vendor' => true]);
        }
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(EnvVariables $envVariables)
    {

        // A bad --config/SUITCASE_CONFIG file must abort the command with a
        // non-zero status instead of terminating the process. Exiting here
        // would kill any embedding process (e.g. tests, queue workers).
        try {
            // If no config path is found, use null to let the Options class resolve using the Config attribute.
            $config = $this->createConfig();
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return 1;
        }

        $this->info('🔧 Laravel Suitcase');
        $this->info('Welcome to the Laravel Shared Hosting Packer!');
        $this->info('This command will help you prepare your Laravel application for deployment on shared hosting.');
        $this->newLine();

        $this->info('Please ensure the config file is set up correctly.');
        $this->reportCheckedConfigPaths();

        if (! $this->validateConfig($config)) {
            return 1;
        }

        $this->info('Before we start, please ensure the following:');
        $this->info('1. Composer dependencies are up to date by running `composer update`.');
        $this->info('2. NPM dependencies are up to date by running `npm update`.');
        $this->info('3. The required Vite assets are built (possibly by running `npm run build`).');
        $this->info('4. The database is in a ready to use state.');
        $this->info('5. The shared hosting .env file is configured correctly for production.');
        $this->newLine();

        $this->warn('Shared hosting is limited and may not support all features of Laravel, such as:');
        $this->warn('1. Asynchronous queues.');
        $this->warn('2. Broadcasting and WebSockets.');
        $this->warn('3. Artisan commands.');
        $this->info('Please ensure your app is compatible with shared hosting environments.');
        $this->newLine();

        if (! $this->confirm('Do you want to continue?', false)) {
            return;
        }

        $eventDispatcher = new EventDispatcher($config);
        $outputter = new ConsoleOutputter($this->output);

        $runner = $this->createRunner($outputter, $envVariables, $eventDispatcher);

        $runner->run($config);

        return 0;
    }

    /**
     * Create the configuration options from the specified config file.
     *
     * @param  string  $configPath  The path to the configuration file.
     * @return array<string, mixed> The configuration options as an associative array.
     */
    protected function createOptionsArrayFromFile(string $configPath): array
    {
        if (! file_exists($configPath)) {
            throw new \InvalidArgumentException("The specified config file does not exist: {$this->absoluteConfigPath($configPath)}");
        }

        if (! is_file($configPath) || ! is_readable($configPath)) {
            throw new \InvalidArgumentException("The specified config file is not a readable file: {$this->absoluteConfigPath($configPath)}");
        }

        $data = require $configPath;

        if (! is_array($data)) {
            throw new \InvalidArgumentException("The specified config file does not return an array: {$this->absoluteConfigPath($configPath)}");
        }

        return $data;
    }

    /**
     * Resolve a path to an absolute form so error messages never report a
     * relative path whose meaning depends on the working directory.
     */
    protected function absoluteConfigPath(string $path): string
    {
        $path = realpath($path) ?: $path;

        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    }

    /**
     * Get the path to the configuration file, either from the command option or the default config path.
     *
     * @return string|null The path to the configuration file, or null if not specified.
     */
    protected function resolveConfigPath(): ?string
    {
        $value = $this->option('config') ?: config('suitcase.config_path');

        if (! $value) {
            return null;
        }

        foreach ($this->getConfigPathCandidates() as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        throw new \InvalidArgumentException(
            "The specified config file does not exist: {$this->absoluteConfigPath($this->resolveConfigPathFromValue($value))}"
        );
    }

    /**
     * Resolve a config file value to its canonical absolute path.
     *
     * Absolute paths are used unchanged; values containing a directory
     * separator or ending in `.php` resolve against base_path(); anything else
     * is treated as a profile name and resolved to config/suitcase.<name>.php.
     */
    protected function resolveConfigPathFromValue(string $value): string
    {
        if (str_starts_with($value, '/')
            || str_starts_with($value, '\\')
            || preg_match('#^[A-Za-z]:[\\\\/]#', $value) === 1) {
            return $value;
        }

        if (str_contains($value, '/') || str_contains($value, '\\') || str_ends_with($value, '.php')) {
            return base_path($value);
        }

        return config_path(Options::CONFIG_ROOT_KEY.".{$value}.php");
    }

    /**
     * The ordered list of paths checked when resolving an alternate config file.
     *
     * @return list<string>
     */
    protected function getConfigPathCandidates(): array
    {
        $value = $this->option('config') ?: config('suitcase.config_path');

        if (! $value) {
            return [];
        }

        return [
            $value,
            base_path($value),
            config_path(Options::CONFIG_ROOT_KEY.".{$value}.php"),
        ];
    }

    /**
     * Report every path that was checked while resolving the config file.
     */
    protected function reportCheckedConfigPaths(): void
    {
        $selected = $this->resolveConfigPath() ?? $this->existingDefaultConfigPath();
        $paths = array_unique([
            config_path(Options::CONFIG_ROOT_KEY.'.php'),
            ...$this->getConfigPathCandidates(),
        ]);

        $this->info('Config file paths checked:');

        foreach ($paths as $path) {
            if (! file_exists($path)) {
                $this->warn("  ❌ {$path}");

                continue;
            }

            $this->info($path === $selected
                ? "  ✅ {$path} (in use)"
                : "  ✅ {$path}");
        }

        $this->newLine();
    }

    protected function getConfigOverrides(): array
    {
        $configPath = $this->resolveConfigPath();
        $overrides = $configPath ? $this->createOptionsArrayFromFile($configPath) : [];

        // Only override the config defaults when a flag is actually passed, so
        // the SKIP_ENV / SKIP_VENDOR environment defaults are preserved.
        // Arr::set() mutates by reference and returns the nested sub-array, so
        // it must not be assigned back over $overrides.
        if ($this->option('skip-env')) {
            Arr::set($overrides, 'skip.env', true);
        }

        if ($this->option('skip-vendor')) {
            Arr::set($overrides, 'skip.vendor', true);
        }

        return $overrides;
    }

    protected function mergeConfigFromFiles(): array
    {
        $overrides = $this->getConfigOverrides();
        $merged = $this->mergeConfig(config(Options::CONFIG_ROOT_KEY, []), $overrides);

        // Never ship the config file that is in use: the alternate when one was
        // selected, otherwise the app's own config/suitcase.php.
        $configFile = $this->resolveConfigPath() ?? $this->existingDefaultConfigPath();

        if ($configFile !== null) {
            $merged = $this->excludeFromPackage($merged, $configFile);
        }

        return $merged;
    }

    /**
     * The app's published config/suitcase.php, when it exists.
     */
    protected function existingDefaultConfigPath(): ?string
    {
        $path = config_path(Options::CONFIG_ROOT_KEY.'.php');

        return file_exists($path) ? $path : null;
    }

    /**
     * Add a config file to the laravel exclude list so it is never copied into
     * the export or the ZIP. Files outside the app are not packaged anyway.
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    protected function excludeFromPackage(array $config, string $configFile): array
    {
        $realPath = realpath($configFile);

        if ($realPath === false || ! Str::startsWith($realPath, base_path())) {
            return $config;
        }

        $relativePath = Str::after($realPath, base_path().DIRECTORY_SEPARATOR);

        $config['exclude']['laravel'][] = Str::replace(['\\', '/'], '/', $relativePath);

        return $config;
    }

    protected function createConfig(): PackConfig
    {
        $data = $this->mergeConfigFromFiles();

        return PackConfig::createWithDefaultValidator($this->createOptions($data));
    }

    /**
     * Merge configuration so a partial override stays valid.
     *
     * Associative maps merge key-by-key; list-valued options (include.*,
     * exclude.*, extra_options, …) are replaced wholesale, not appended.
     */
    protected function mergeConfig(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            $current = $base[$key] ?? null;

            if (
                is_array($value)
                && is_array($current)
                && ! array_is_list($value)
                && ! array_is_list($current)
            ) {
                $base[$key] = $this->mergeConfig($current, $value);
            } else {
                $base[$key] = $value;
            }
        }

        return $base;
    }

    /**
     * Create the configuration options from the specified config file or defaults.
     *
     * @param  array<string, mixed>  $data  The configuration data as an associative array.
     * @return Options The configuration options.
     */
    protected function createOptions(array $data): Options
    {
        return Options::createFromArray($data);
    }

    /**
     * Validate the configuration.
     */
    protected function validateConfig(PackConfig $config): bool
    {
        try {
            $config->validate();
        } catch (\InvalidArgumentException $e) {
            $this->error('Configuration error: '.$e->getMessage());

            return false;
        }

        return true;
    }

    /**
     * Create the packer runner.
     */
    protected function createRunner(Outputter $outputter, EnvVariables $envVariables, EventDispatcher $eventDispatcher): PackForSharedHostingRunner
    {
        return new PackForSharedHostingRunner(
            outputter: $outputter,
            envVariables: $envVariables,
            eventDispatcher: $eventDispatcher
        );
    }
}
