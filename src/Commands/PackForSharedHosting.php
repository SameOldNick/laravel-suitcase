<?php

namespace SameOldNick\LaraHostPack\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use SameOldNick\LaraHostPack\Config\Repositories\ConfigRepository;
use SameOldNick\LaraHostPack\Contracts\Config\PackConfig;
use SameOldNick\LaraHostPack\Contracts\EnvVariables;
use SameOldNick\LaraHostPack\Support\EventDispatcher;

class PackForSharedHosting extends Command
{
    use Concerns\DumpsDatabase;
    use Concerns\HandlesFileExport;
    use Concerns\HandlesZipping;
    use Concerns\HasConfig;
    use Concerns\PreparesDirectories;
    use Concerns\PreparesFiles;

    protected $signature = 'hostpack
                            {--skip-vendor : Skip vendor directory}
                            {--skip-env : Skip .env file}';

    protected $description = 'Package Laravel app for deployment to shared hosting (e.g., cPanel)';

    public function handle(PackConfig $config, EnvVariables $envVariables)
    {
        $eventDispatcher = new EventDispatcher($this, $config);

        $this->info('🔧 LaraHostPack');
        $this->info('Welcome to the Laravel Shared Hosting Packer!');
        $this->info('This command will help you prepare your Laravel application for deployment on shared hosting.');
        $this->newLine();

        $this->info('Please ensure the config file is set up correctly.');
        $this->info('You can find the config file at: '.config_path(ConfigRepository::getConfigRootKey().'.php'));
        $this->newLine();

        if (! $this->setConfig($config)->validateConfig()) {
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

        $this->info('Preparing app for shared hosting...');
        $eventDispatcher->dispatch('hostpack.preparing');

        // Create the ZIP file if it doesn't exist
        if (File::put($this->getConfig()->getZipPath(), '') === false) {
            $this->error("The ZIP file is not writable: {$this->getConfig()->getZipPath()}");

            return 1;
        }

        $this->prepareExportDirectories();
        $eventDispatcher->dispatch('hostpack.directories.prepared');

        if ($config->getDbDumpEnabled()) {
            $this->dumpDatabase();
            $eventDispatcher->dispatch('hostpack.database.dumped');
        }

        $this->exportFiles();
        $eventDispatcher->dispatch('hostpack.files.exported');

        // Update index.php to point to the correct Laravel directory
        $this->updateConstantsFile("{$config->getPublicPath()}/constants.php");

        if (! $this->option('skip-env')) {
            // Update .env file for shared hosting
            $destinationEnvFilePath = "{$config->getLaravelPath()}/.env";
            $this->updateEnvFile($destinationEnvFilePath, $envVariables->getCustomizedVariables());

            $eventDispatcher->dispatch('hostpack.env.updated', [
                'envFilePath' => $destinationEnvFilePath,
                'envVariables' => $envVariables->getCustomizedVariables(),
            ]);
        }

        $this->createInstallFile();
        $eventDispatcher->dispatch('hostpack.install.file.created');

        $this->zipPackage();
        $eventDispatcher->dispatch('hostpack.zipped');

        $this->newLine();
        $this->info('✅ Package created successfully: '.$this->getConfig()->getZipPath());

        $eventDispatcher->dispatch('hostpack.completed');

        $this->info('To deploy your app, follow the instructions in the INSTALL.txt file.');

        $this->newLine();

        return 0;
    }
}
