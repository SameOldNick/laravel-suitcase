<?php

namespace SameOldNick\LaravelSuitcase\Runners\Steps;

use Illuminate\Support\Facades\File;
use SameOldNick\LaravelSuitcase\Contracts\PackPipelineStep;
use SameOldNick\LaravelSuitcase\Exceptions\ZipFileNotWritable;
use SameOldNick\LaravelSuitcase\Runners\PackPipelineContext;

class PreparesZipFile implements PackPipelineStep
{
    /**
     * Prepare the ZIP file for the export process.
     */
    public function perform(PackPipelineContext $context): void
    {
        $context->getOutputter()->info('Preparing app for shared hosting...');
        $context->getEventDispatcher()?->dispatch('suitcase.preparing');

        // Create the ZIP file if it doesn't exist
        if (File::put($context->getConfig()->getZipPath(), '') === false) {
            $context->getOutputter()->error("The ZIP file is not writable: {$context->getConfig()->getZipPath()}");

            throw new ZipFileNotWritable($context->getConfig()->getZipPath());
        }
    }
}
