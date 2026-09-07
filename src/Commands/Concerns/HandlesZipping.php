<?php

namespace SameOldNick\LaravelSuitcase\Commands\Concerns;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

/**
 * @mixin \SameOldNick\LaravelSuitcase\Commands\PackForSharedHosting
 */
trait HandlesZipping
{
    /**
     * Zip the exported files into a package.
     */
    protected function zipPackage(): void
    {
        /**
         * @var \SameOldNick\LaravelSuitcase\Commands\PackForSharedHosting $this
         */
        $exportPath = $this->getConfig()->getExportPath();
        $zipPath = $this->getConfig()->getZipPath();

        $zip = new ZipArchive;

        if (! $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
            $this->error('Failed to create zip file: '.$zipPath);

            return;
        }

        $this->info('Zipping files...');

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($exportPath),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        $bar = $this->output->createProgressBar();

        $bar->start();
        $bar->setFormat('verbose');

        foreach ($files as $file) {
            if (! $file->isDir()) {
                $filePath = $file->getRealPath();

                $relativePath = substr($filePath, strlen($exportPath) + 1);
                $relativePath = str_replace('\\', '/', $relativePath); // Normalize for zip

                $bar->setMessage('Zipping: '.$relativePath);

                $zip->addFile($filePath, $relativePath);
            }

            $bar->advance();
        }

        $bar->finish();

        $this->newLine();

        $zip->close();

        $this->info('Zipping completed!');
    }
}
