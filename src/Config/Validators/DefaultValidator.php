<?php

namespace SameOldNick\LaraHostPack\Config\Validators;

use Illuminate\Support\Facades\File;
use SameOldNick\LaraHostPack\Contracts\Config\PackConfig as PackConfigContract;
use SameOldNick\LaraHostPack\Contracts\Config\ValidatesConfig;

class DefaultValidator implements ValidatesConfig
{
    /**
     * @inheritDoc
     */
    public function validate(PackConfigContract $packConfig): void
    {
        if (empty($this->config['export_dir'])) {
            throw new \InvalidArgumentException('Export directory is not set in the configuration.');
        }

        if (empty($this->config['zip_name'])) {
            throw new \InvalidArgumentException('Zip name is not set in the configuration.');
        }

        if (empty($this->config['env_file'])) {
            throw new \InvalidArgumentException('Env file path is not set in the configuration.');
        }

        if (! File::exists($packConfig->getEnvFilePath())) {
            throw new \InvalidArgumentException('The specified .env file does not exist: ' . $packConfig->getEnvFilePath());
        }

        if (! File::exists($packConfig->getIndexStubPath())) {
            throw new \InvalidArgumentException("The index.php stub file does not exist: {$packConfig->getIndexStubPath()}");
        }

        if (! File::exists($packConfig->getConstantsStubPath())) {
            throw new \InvalidArgumentException("The constants.php stub file does not exist: {$packConfig->getConstantsStubPath()}");
        }
    }
}
