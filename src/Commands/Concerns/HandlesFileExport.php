<?php

namespace SameOldNick\LaraHostPack\Commands\Concerns;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * @mixin \SameOldNick\LaraHostPack\Commands\PackForSharedHosting
 */
trait HandlesFileExport
{
    /**
     * Expores files to the export directory.
     */
    protected function exportFiles(): void
    {
        /**
         * @var \SameOldNick\LaraHostPack\Commands\PackForSharedHosting $this
         */
        $this->info('Exporting files...');

        $basePath = base_path();

        // Public directory
        $this->exportPublicDirectory($basePath . '/public');

        // Laravel core
        $this->exportLaravelDirectory($basePath);

        $this->newLine();

        // Create additional files
        $this->exportAdditionalFiles();
    }

    /**
     * Exports the public directory to the export directory.
     *
     * @param  string  $source  Source directory path
     * @return void
     */
    protected function exportPublicDirectory(string $source)
    {
        /**
         * @var \SameOldNick\LaraHostPack\Commands\PackForSharedHosting $this
         */
        $this->info('Exporting public directory...');

        $this->copyDirectory($source, $this->getConfig()->getPublicPath(), $this->cleanList($this->getConfig()->getPublicIncludes()), $this->cleanList($this->getConfig()->getPublicExcludes()));

        $this->info('Exported public directory successfully.');
    }

    /**
     * Exports the Laravel core directory to the export directory.
     *
     * @param  string  $source  Source directory path
     * @return void
     */
    protected function exportLaravelDirectory(string $source)
    {
        /**
         * @var \SameOldNick\LaraHostPack\Commands\PackForSharedHosting $this
         */
        $this->info('Exporting Laravel core directory...');

        $includes = $this->cleanList($this->getConfig()->getLaravelIncludes());
        $excludes = $this->cleanList($this->getConfig()->getLaravelExcludes());

        if ($this->option('skip-vendor')) {
            $excludes[] = 'vendor/*';
        }

        $this->copyDirectory($source, $this->getConfig()->getLaravelPath(), $includes, $excludes);

        $this->info('Exported Laravel core directory successfully.');
    }

    /**
     * Exports additional files to the export directory.
     *
     * @return void
     */
    protected function exportAdditionalFiles()
    {
        /**
         * @var \SameOldNick\LaraHostPack\Commands\PackForSharedHosting $this
         */
        $publicPath = $this->getConfig()->getPublicPath();
        $laravelPath = $this->getConfig()->getLaravelPath();

        $files = [
            [$this->getConfig()->getConstantsStubPath(), "$publicPath/constants.php"],
            [$this->getConfig()->getIndexStubPath(), "$publicPath/index.php"],
        ];

        if (! $this->option('skip-env')) {
            $files[] = [$this->getConfig()->getEnvFilePath(), "$laravelPath/.env"];
        }

        $this->info('Exporting additional files...');

        $bar = $this->output->createProgressBar(count($files));

        $bar->setFormat('verbose');
        $bar->start();

        foreach ($files as [$source, $destination]) {
            if (! File::exists($source)) {
                throw new \RuntimeException("Source file does not exist: {$source}");
            }

            $filename = basename($destination);

            File::copy($source, $destination);

            $bar->setMessage("Exported {$filename} file successfully.");
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info('Exported additional files successfully.');
    }

    /**
     * Recursively iterates through a directory and executes a callback for each file or directory.
     *
     * @param  string  $directory  Directory to iterate through
     * @param  callable  $callback  Callback function to execute for each file or directory
     * @param  bool  $ignoreLinks  Whether to ignore symbolic links
     */
    protected function recurseDirectory(string $directory, callable $callback, bool $ignoreLinks = true): void
    {
        /**
         * @var \SameOldNick\LaraHostPack\Commands\PackForSharedHosting $this
         */
        $source = $this->normalizePath(realpath($directory));
        $items = scandir($source);

        if ($items === false) {
            $this->output->error("Failed to read directory: {$source}");

            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullPath = $this->normalizePath("$source/$item");

            if ($ignoreLinks && is_link($fullPath)) {
                $this->output->warning("Skipping symlink: {$fullPath}");

                continue;
            }

            if (is_dir($fullPath)) {
                $callback($item, $fullPath);

                $this->recurseDirectory($fullPath, $callback, $ignoreLinks);
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
    protected function copyDirectory(string $source, string $destination, array $includes, array $excludes): void
    {
        /**
         * @var \SameOldNick\LaraHostPack\Commands\PackForSharedHosting $this
         */
        $source = $this->normalizePath(realpath($source));
        $destination = $this->normalizePath($destination);

        if (! File::exists($destination)) {
            File::makeDirectory($destination, 0755, true);
        }

        $bar = $this->output->createProgressBar();

        $bar->setFormat('verbose');
        $bar->start();

        $this->recurseDirectory($source, function ($item, $itemPath) use ($source, $destination, $includes, $excludes, $bar) {
            $bar->advance();

            $sourchPathRelative = sprintf('%s%s', str_replace($source . '/', '', $itemPath), File::isDirectory($itemPath) ? '/' : '');

            // Check if the item is included or excluded
            $included = empty($includes) || $this->filesInList($includes, $sourchPathRelative);
            $excluded = ! empty($excludes) && $this->filesInList($excludes, $sourchPathRelative);

            if (! $included) {
                $bar->setMessage("Skipping file or directory not in includes list: {$itemPath}");

                return;
            }

            if ($excluded) {
                $bar->setMessage("Skipping excluded file or directory: {$itemPath}");

                return;
            }

            $destinationPath = $this->normalizePath($destination . '/' . $sourchPathRelative);

            if (is_dir($itemPath)) {
                $bar->setMessage("Creating directory: {$destinationPath}");
                File::ensureDirectoryExists($destinationPath, 0755, true);
            } elseif (is_file($itemPath)) {
                $bar->setMessage("Copying file: {$destinationPath}");
                File::copy($itemPath, $destinationPath);
            }
        });

        $bar->finish();
        $this->newLine();
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
