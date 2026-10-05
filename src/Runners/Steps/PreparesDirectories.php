<?php

namespace SameOldNick\LaravelSuitcase\Runners\Steps;

use Illuminate\Support\Facades\File;
use SameOldNick\LaravelSuitcase\Commands\PackForSharedHosting;
use SameOldNick\LaravelSuitcase\Contracts\PackPipelineStep;
use SameOldNick\LaravelSuitcase\Exceptions\ExportDirectoryNotCreated;
use SameOldNick\LaravelSuitcase\Exceptions\ExportDirectoryNotDeleted;
use SameOldNick\LaravelSuitcase\Runners\PackPipelineContext;

/**
 * @mixin PackForSharedHosting
 */
class PreparesDirectories implements PackPipelineStep
{
    /**
     * Prepare the necessary directories for the export process.
     */
    public function perform(PackPipelineContext $context): void
    {
        $context->getOutputter()->info('Preparing export directories...');

        $this->prepareExportDirectory($context, $context->getConfig()->getExportPath());
        $this->prepareLaravelDirectory($context, $context->getConfig()->getLaravelPath());
        $this->preparePublicDirectory($context, $context->getConfig()->getPublicPath());

        if (File::put("{$context->getConfig()->getExportPath()}/.gitignore", "*\n!.gitignore\n")) {
            $context->getOutputter()->info('.gitignore file created successfully in the export directory.');
        } else {
            $context->getOutputter()->error('Failed to create .gitignore file in the export directory.');
        }

        $context->getOutputter()->info('Export directories prepared successfully.');

        $context->getEventDispatcher()?->dispatch('suitcase.directories.prepared', [
            'export_path' => $context->getConfig()->getExportPath(),
            'laravel_path' => $context->getConfig()->getLaravelPath(),
            'public_path' => $context->getConfig()->getPublicPath(),
        ]);
    }

    /**
     * Prepare the export directory.
     */
    protected function prepareExportDirectory(PackPipelineContext $context, string $path): void
    {
        $context->getOutputter()->info("Preparing export directory: $path...");

        if (File::deleteDirectory($path)) {
            $context->getOutputter()->info("Deleted existing export directory: $path.");
        } else {
            throw new ExportDirectoryNotDeleted($path);
        }

        if (File::makeDirectory($path, 0755, true)) {
            $context->getOutputter()->info("Export directory created successfully: $path.");
        } else {
            throw new ExportDirectoryNotCreated($path);
        }

        $this->createGitIgnoreFile($context, $path);

        $context->getOutputter()->info("Export directory prepared successfully: $path.");
    }

    /**
     * Prepare the Laravel directory.
     */
    protected function prepareLaravelDirectory(PackPipelineContext $context, string $path): void
    {
        $context->getOutputter()->info("Preparing Laravel directory: $path...");

        File::makeDirectory($path, 0755, true);

        $directories = [
            'bootstrap/cache',
            'storage/app',
            'storage/framework/cache',
            'storage/framework/sessions',
            'storage/framework/views',
            'storage/logs',
        ];

        $bar = $context->getOutputter()->createProgressBar(count($directories));

        $bar?->setFormat('verbose');
        $bar?->start();

        foreach ($directories as $dir) {
            File::makeDirectory("$path/$dir", 0755, true);
            $bar?->setMessage("Created directory: $path/$dir");

            $this->createGitIgnoreFile($context, "$path/$dir");
            $bar?->setMessage(".gitignore file created successfully in: $path/$dir");

            $bar?->advance();
        }

        $bar?->finish();
        $context->getOutputter()->newLine();

        $context->getOutputter()->info("Laravel directory prepared successfully: $path.");
    }

    /**
     * Prepare the public directory.
     */
    protected function preparePublicDirectory(PackPipelineContext $context, string $path): void
    {
        $context->getOutputter()->info("Preparing public directory: $path...");

        if (File::makeDirectory($path, 0755, true)) {
            $context->getOutputter()->info("Public directory prepared successfully: $path.");
        } else {
            $context->getOutputter()->error("Failed to prepare public directory: $path.");
        }
    }

    /**
     * Create a .gitignore file in the specified path.
     */
    protected function createGitIgnoreFile(PackPipelineContext $context, string $path): void
    {
        if (File::put("$path/.gitignore", "*\n!.gitignore\n")) {
            $context->getOutputter()->info(".gitignore file created successfully in: $path.");
        } else {
            $context->getOutputter()->error("Failed to create .gitignore file in: $path.");
        }
    }
}
