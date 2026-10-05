<?php

namespace SameOldNick\LaravelSuitcase\Runners\Steps;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use SameOldNick\LaravelSuitcase\Contracts\PackPipelineStep;
use SameOldNick\LaravelSuitcase\Runners\PackPipelineContext;

class HandlesFileExport implements PackPipelineStep
{
    /**
     * Handles the file export process.
     */
    public function __invoke(PackPipelineContext $context): void
    {
        $context->getOutputter()->info('Exporting files...');

        $basePath = base_path();

        // Public directory
        $this->exportPublicDirectory($context, $basePath.'/public');

        // Laravel core
        $this->exportLaravelDirectory($context, $basePath);

        $context->getOutputter()->newLine();

        // Create additional files
        $this->exportAdditionalFiles($context);

        $context->getOutputter()->info('File export completed.');

        $context->getEventDispatcher()?->dispatch('suitcase.files.exported', [
            'export_path' => $context->getConfig()->getExportPath(),
        ]);
    }

    /**
     * Exports the public directory to the export directory.
     *
     * @param  string  $source  Source directory path
     * @return void
     */
    protected function exportPublicDirectory(PackPipelineContext $context, string $source)
    {
        $context->getOutputter()->info('Exporting public directory...');

        $this->copyDirectory($context, $source, $context->getConfig()->getPublicPath(), $this->cleanList($context->getConfig()->getPublicIncludes()), $this->cleanList($context->getConfig()->getPublicExcludes()));

        $context->getOutputter()->info('Exported public directory successfully.');
    }

    /**
     * Exports the Laravel core directory to the export directory.
     *
     * @param  string  $source  Source directory path
     * @return void
     */
    protected function exportLaravelDirectory(PackPipelineContext $context, string $source)
    {
        $context->getOutputter()->info('Exporting Laravel core directory...');

        $includes = $this->cleanList($context->getConfig()->getLaravelIncludes());
        $excludes = $this->cleanList($context->getConfig()->getLaravelExcludes());

        if ($context->getConfig()->shouldSkipVendor()) {
            $excludes[] = 'vendor/*';
        }

        $this->copyDirectory($context, $source, $context->getConfig()->getLaravelPath(), $includes, $excludes);

        $context->getOutputter()->info('Exported Laravel core directory successfully.');
    }

    /**
     * Exports additional files to the export directory.
     *
     * @return void
     */
    protected function exportAdditionalFiles(PackPipelineContext $context)
    {
        $publicPath = $context->getConfig()->getPublicPath();
        $laravelPath = $context->getConfig()->getLaravelPath();

        $files = [
            [$context->getConfig()->getConstantsStubPath(), "$publicPath/constants.php"],
            [$context->getConfig()->getIndexStubPath(), "$publicPath/index.php"],
        ];

        if (! $context->getConfig()->shouldSkipEnv()) {
            $files[] = [$context->getConfig()->getEnvFilePath(), "$laravelPath/.env"];
        }

        $context->getOutputter()->info('Exporting additional files...');

        $bar = $context->getOutputter()->createProgressBar(count($files));

        $bar?->setFormat('verbose');
        $bar?->start();

        foreach ($files as [$source, $destination]) {
            if (! File::exists($source)) {
                throw new \RuntimeException("Source file does not exist: {$source}");
            }

            $filename = basename($destination);

            if (File::copy($source, $destination)) {
                $bar?->setMessage("Exported {$filename} file successfully.");
            } else {
                $bar?->setMessage("Failed to export {$filename} file.");
            }

            $bar?->advance();
        }

        $bar?->finish();
        $context->getOutputter()->newLine();

        $context->getOutputter()->info('Exported additional files successfully.');
    }

    /**
     * Recursively iterates through a directory and executes a callback for each file or directory.
     *
     * @param  string  $directory  Directory to iterate through
     * @param  callable  $callback  Callback function to execute for each file or directory
     * @param  bool  $ignoreLinks  Whether to ignore symbolic links
     */
    protected function recurseDirectory(PackPipelineContext $context, string $directory, callable $callback, bool $ignoreLinks = true): void
    {
        $source = $this->normalizePath(realpath($directory));
        $items = scandir($source);

        if ($items === false) {
            $context->getOutputter()->error("Failed to read directory: {$source}");

            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullPath = $this->normalizePath("$source/$item");

            if ($ignoreLinks && is_link($fullPath)) {
                $context->getOutputter()->warning("Skipping symlink: {$fullPath}");

                continue;
            }

            if (is_dir($fullPath)) {
                $callback($item, $fullPath);

                $this->recurseDirectory($context, $fullPath, $callback, $ignoreLinks);
            } elseif (is_file($fullPath)) {
                $callback($item, $fullPath);
            }
        }
    }

    /**
     * Copies a directory and its contents to a new location, including or excluding files based on the provided lists.
     *
     * @param  string  $source  Source directory path
     * @param  string  $destination  Destination directory path
     * @param  array  $includes  List of files to include (empty means all)
     * @param  array  $excludes  List of files to exclude (empty means none)
     */
    protected function copyDirectory(PackPipelineContext $context, string $source, string $destination, array $includes, array $excludes): void
    {
        $source = $this->normalizePath(realpath($source));
        $destination = $this->normalizePath($destination);

        if (! File::exists($destination)) {
            File::makeDirectory($destination, 0755, true);
        }

        $bar = $context->getOutputter()->createProgressBar();

        $bar?->setFormat('verbose');
        $bar?->start();

        $this->recurseDirectory($context, $source, function ($item, $itemPath) use ($source, $destination, $includes, $excludes, $bar) {
            $bar?->advance();

            $sourchPathRelative = sprintf('%s%s', str_replace($source.'/', '', $itemPath), File::isDirectory($itemPath) ? '/' : '');

            // Check if the item is included or excluded
            $included = empty($includes) || $this->filesInList($includes, $sourchPathRelative);
            $excluded = ! empty($excludes) && $this->filesInList($excludes, $sourchPathRelative);

            if (! $included) {
                $bar?->setMessage("Skipping file or directory not in includes list: {$itemPath}");

                return;
            }

            if ($excluded) {
                $bar?->setMessage("Skipping excluded file or directory: {$itemPath}");

                return;
            }

            $destinationPath = $this->normalizePath($destination.'/'.$sourchPathRelative);

            if (is_dir($itemPath)) {
                $bar?->setMessage("Creating directory: {$destinationPath}");
                File::ensureDirectoryExists($destinationPath, 0755, true);
            } elseif (is_file($itemPath)) {
                $bar?->setMessage("Copying file: {$destinationPath}");

                if (File::copy($itemPath, $destinationPath)) {
                    $bar?->setMessage("Copied file successfully: {$destinationPath}");
                } else {
                    $bar?->setMessage("Failed to copy file: {$destinationPath}");
                }
            }
        });

        $bar?->finish();
        $context->getOutputter()->newLine();
    }

    /**
     * Cleans the list of includes or excludes by removing the wildcard if it's the only item in the list.
     *
     * @param  array  $list  List of includes or excludes
     * @return array Cleaned list
     */
    private function cleanList(array $list): array
    {
        return count($list) === 1 && $list[0] === '*' ? [] : $list;
    }

    /**
     * Checks if a file or directory is in the include or exclude list.
     *
     * @param  array  $patterns  List of patterns to check against
     * @param  string  $relativePath  Relative path of the file or directory
     * @return bool True if the file or directory is in the list, false otherwise
     */
    private function filesInList(array $patterns, string $relativePath): bool
    {
        return Str::is($patterns, $relativePath, true) || Str::is($patterns, basename($relativePath), true);
    }

    /**
     * Normalizes the path by replacing backslashes with forward slashes.
     *
     * @param  string  $path  Path to normalize
     * @return string Normalized path
     */
    private function normalizePath(string $path): string
    {
        return str_replace('\\', '/', $path);
    }
}
