<?php

namespace SameOldNick\LaravelSuitcase\Config\Validators;

use Illuminate\Support\Facades\File;
use SameOldNick\LaravelSuitcase\Contracts\Config\PackConfig as PackConfigContract;
use SameOldNick\LaravelSuitcase\Contracts\Config\ValidatesConfig;

class DefaultValidator implements ValidatesConfig
{
    /**
     * {@inheritDoc}
     */
    public function validate(PackConfigContract $packConfig): void
    {
        if (empty($packConfig->getExportPath())) {
            throw new \InvalidArgumentException('Export directory is not set in the configuration.');
        }

        if (empty($packConfig->getZipPath())) {
            throw new \InvalidArgumentException('Zip name is not set in the configuration.');
        }

        if (empty($packConfig->getEnvFilePath())) {
            throw new \InvalidArgumentException('Env file path is not set in the configuration.');
        }

        if (! File::exists($packConfig->getEnvFilePath())) {
            throw new \InvalidArgumentException('The specified .env file does not exist: '.$packConfig->getEnvFilePath());
        }

        if (! File::exists($packConfig->getIndexStubPath())) {
            throw new \InvalidArgumentException("The index.php stub file does not exist: {$packConfig->getIndexStubPath()}");
        }

        if (! File::exists($packConfig->getConstantsStubPath())) {
            throw new \InvalidArgumentException("The constants.php stub file does not exist: {$packConfig->getConstantsStubPath()}");
        }
    }
}
