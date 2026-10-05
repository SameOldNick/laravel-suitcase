<?php

namespace SameOldNick\LaravelSuitcase\Commands;

use Illuminate\Console\Command;
use SameOldNick\LaravelSuitcase\Config\Options;
use SameOldNick\LaravelSuitcase\Contracts\Config\PackConfig;
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
    public function handle(PackConfig $config, EnvVariables $envVariables)
    {
        $this->info('🔧 Laravel Suitcase');
        $this->info('Welcome to the Laravel Shared Hosting Packer!');
        $this->info('This command will help you prepare your Laravel application for deployment on shared hosting.');
        $this->newLine();

        $this->info('Please ensure the config file is set up correctly.');
        $this->info('You can find the config file at: '.config_path(Options::CONFIG_ROOT_KEY.'.php'));
        $this->newLine();

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
