<?php

namespace SameOldNick\LaravelSuitcase\Runners\Steps;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SameOldNick\LaravelSuitcase\Contracts\PackPipelineStep;
use SameOldNick\LaravelSuitcase\Exceptions\ZipFileNotCreated;
use SameOldNick\LaravelSuitcase\Runners\PackPipelineContext;
use ZipArchive;

class HandlesZipping implements PackPipelineStep
{
    /**
     * Handle the zipping of files in the export directory into a ZIP file.
     */
    public function perform(PackPipelineContext $context): void
    {
        $exportPath = $context->getConfig()->getExportPath();
        $zipPath = $context->getConfig()->getZipPath();

        $zip = new ZipArchive;

        if (! $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
            throw new ZipFileNotCreated($zipPath);
        }

        $context->getOutputter()->info('Zipping files...');

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($exportPath),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        $bar = $context->getOutputter()->createProgressBar();

        $bar?->start();
        $bar?->setFormat('verbose');

        foreach ($files as $file) {
            if (! $file->isDir()) {
                $filePath = $file->getRealPath();

                $relativePath = substr($filePath, strlen($exportPath) + 1);
                $relativePath = str_replace('\\', '/', $relativePath); // Normalize for zip

                $bar?->setMessage('Zipping: '.$relativePath);

                if (! $zip->addFile($filePath, $relativePath)) {
                    $context->getOutputter()->error('Failed to add file to zip: '.$relativePath);
                }
            }

            $bar?->advance();
        }

        $bar?->finish();

        $context->getOutputter()->newLine();

        $zip->close();

        $context->getOutputter()->info('Zipping completed!');

        $context->getEventDispatcher()?->dispatch('suitcase.zipped', [
            'export_path' => $exportPath,
            'zip_path' => $zipPath,
        ]);
    }
}
