<?php

namespace SameOldNick\LaravelSuitcase\Config;

use SameOldNick\LaravelSuitcase\Contracts\Config\Options;
use SameOldNick\LaravelSuitcase\Contracts\Config\ValidatesConfig;

class PackConfig
{
    public function __construct(
        protected readonly Options $options,
        protected readonly ValidatesConfig $validator
    ) {
        //
    }

    public function validate(): void
    {
        $this->getValidator()->validate($this);
    }

    public function getExportPath(): string
    {
        return $this->getOptions()->getExportPath();
    }

    public function getPublicPath(): string
    {
        return $this->getOptions()->getPublicPath();
    }

    public function getLaravelPath(): string
    {
        return $this->getOptions()->getLaravelPath();
    }

    public function getZipPath(): string
    {
        return $this->getOptions()->getZipPath();
    }

    public function getEnvFilePath(): string
    {
        return $this->getOptions()->getEnvFilePath();
    }

    public function getDbDumpEnabled(): bool
    {
        return $this->getOptions()->getDbDumpEnabled();
    }

    public function getDbConnections(): array
    {
        return $this->getOptions()->getDbConnections();
    }

    public function getRootFiles(): array
    {
        return $this->getOptions()->getRootFiles();
    }

    public function getLaravelIncludes(): array
    {
        return $this->getOptions()->getLaravelIncludes();
    }

    public function getPublicIncludes(): array
    {
        return $this->getOptions()->getPublicIncludes();
    }

    public function getLaravelExcludes(): array
    {
        return $this->getOptions()->getLaravelExcludes();
    }

    public function getPublicExcludes(): array
    {
        return $this->getOptions()->getPublicExcludes();
    }

    public function getIndexStubPath(): string
    {
        return $this->getOptions()->getIndexStubPath();
    }

    public function getConstantsStubPath(): string
    {
        return $this->getOptions()->getConstantsStubPath();
    }

    public function getRemoteLaravelPath(): string
    {
        return $this->getOptions()->getRemoteLaravelPath();
    }

    public function getRemotePublicPath(): string
    {
        return $this->getOptions()->getRemotePublicPath();
    }

    public function shouldSkipEnv(): bool
    {
        return $this->getOptions()->shouldSkipEnv();
    }

    public function shouldSkipVendor(): bool
    {
        return $this->getOptions()->shouldSkipVendor();
    }

    /**
     * Gets the options instance.
     */
    protected function getOptions(): Options
    {
        return $this->options;
    }

    /**
     * Gets the validator instance.
     */
    protected function getValidator(): ValidatesConfig
    {
        return $this->validator;
    }
}
