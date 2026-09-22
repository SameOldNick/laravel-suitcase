<?php

namespace SameOldNick\LaravelSuitcase\Commands\Concerns;

use Illuminate\Support\Facades\File;
use SameOldNick\LaravelSuitcase\Commands\PackForSharedHosting;

/**
 * @mixin PackForSharedHosting
 */
trait PreparesDirectories
{
    /**
     * Prepare the export directories.
     */
    protected function prepareExportDirectories(): void
    {
        /**
         * @var PackForSharedHosting $this
         */
        $this->info('Preparing export directories...');

        $this->prepareExportDirectory($this->getConfig()->getExportPath());
        $this->prepareLaravelDirectory($this->getConfig()->getLaravelPath());
        $this->preparePublicDirectory($this->getConfig()->getPublicPath());

        if (File::put("{$this->getConfig()->getExportPath()}/.gitignore", "*\n!.gitignore\n")) {
            $this->info('.gitignore file created successfully in the export directory.');
        } else {
            $this->error('Failed to create .gitignore file in the export directory.');
        }

        $this->info('Export directories prepared successfully.');
    }

    /**
     * Prepare the export directory.
     */
    protected function prepareExportDirectory(string $path): void
    {
        /**
         * @var PackForSharedHosting $this
         */
        $this->info("Preparing export directory: $path...");

        if (File::deleteDirectory($path)) {
            $this->info("Deleted existing export directory: $path.");
        } else {
            $this->error("Failed to delete existing export directory: $path.");
        }

        if (File::makeDirectory($path, 0755, true)) {
            $this->info("Export directory created successfully: $path.");
        } else {
            $this->error("Failed to create export directory: $path.");
        }

        $this->createGitIgnoreFile($path);

        $this->info("Export directory prepared successfully: $path.");
    }

    /**
     * Prepare the Laravel directory.
     */
    protected function prepareLaravelDirectory(string $path): void
    {
        /**
         * @var PackForSharedHosting $this
         */
        $this->info("Preparing Laravel directory: $path...");

        File::makeDirectory($path, 0755, true);

        $directories = [
            'bootstrap/cache',
            'storage/app',
            'storage/framework/cache',
            'storage/framework/sessions',
            'storage/framework/views',
            'storage/logs',
        ];

        $bar = $this->output->createProgressBar(count($directories));

        $bar->setFormat('verbose');
        $bar->start();

        foreach ($directories as $dir) {
            File::makeDirectory("$path/$dir", 0755, true);
            $bar->setMessage("Created directory: $path/$dir");

            $this->createGitIgnoreFile("$path/$dir");
            $bar->setMessage(".gitignore file created successfully in: $path/$dir");

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("Laravel directory prepared successfully: $path.");
    }

    /**
     * Prepare the public directory.
     */
    protected function preparePublicDirectory(string $path): void
    {
        /**
         * @var PackForSharedHosting $this
         */
        $this->info("Preparing public directory: $path...");

        if (File::makeDirectory($path, 0755, true)) {
            $this->info("Public directory prepared successfully: $path.");
        } else {
            $this->error("Failed to prepare public directory: $path.");
        }
    }

    /**
     * Create a .gitignore file in the specified path.
     */
    protected function createGitIgnoreFile(string $path): void
    {
        if (File::put("$path/.gitignore", "*\n!.gitignore\n")) {
            $this->info(".gitignore file created successfully in: $path.");
        } else {
            $this->error("Failed to create .gitignore file in: $path.");
        }
    }
}
