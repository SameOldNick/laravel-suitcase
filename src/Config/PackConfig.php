<?php

namespace SameOldNick\LaraHostPack\Config;

use SameOldNick\LaraHostPack\Contracts\Config\Options;
use SameOldNick\LaraHostPack\Contracts\Config\PackConfig as PackConfigContract;
use SameOldNick\LaraHostPack\Contracts\Config\ValidatesConfig;

class PackConfig implements PackConfigContract
{
    public function __construct(
        protected readonly Options $options,
        protected readonly ValidatesConfig $validator
    ) {}

    /**
     * {@inheritDoc}
     */
    public function validate(): void
    {
        $this->getValidator()->validate($this);
    }

    /**
     * {@inheritDoc}
     */
    public function getExportPath(): string
    {
        return $this->getOptions()->getExportPath();
    }

    /**
     * {@inheritDoc}
     */
    public function getPublicPath(): string
    {
        return $this->getOptions()->getPublicPath();
    }

    /**
     * {@inheritDoc}
     */
    public function getLaravelPath(): string
    {
        return $this->getOptions()->getLaravelPath();
    }

    /**
     * {@inheritDoc}
     */
    public function getZipPath(): string
    {
        return $this->getOptions()->getZipPath();
    }

    /**
     * {@inheritDoc}
     */
    public function getEnvFilePath(): string
    {
        return $this->getOptions()->getEnvFilePath();
    }

    /**
     * {@inheritDoc}
     */
    public function getDbDumpEnabled(): bool
    {
        return $this->getOptions()->getDbDumpEnabled();
    }

    /**
     * {@inheritDoc}
     */
    public function getDbConnection(): string
    {
        return $this->getOptions()->getDbConnection();
    }

    /**
     * {@inheritDoc}
     */
    public function getDbDumpOptions(): array
    {
        return $this->getOptions()->getDbDumpOptions();
    }

    /**
     * {@inheritDoc}
     */
    public function getRootFiles(): array
    {
        return $this->getOptions()->getRootFiles();
    }

    /**
     * {@inheritDoc}
     */
    public function getLaravelIncludes(): array
    {
        return $this->getOptions()->getLaravelIncludes();
    }

    /**
     * {@inheritDoc}
     */
    public function getPublicIncludes(): array
    {
        return $this->getOptions()->getPublicIncludes();
    }

    /**
     * {@inheritDoc}
     */
    public function getLaravelExcludes(): array
    {
        return $this->getOptions()->getLaravelExcludes();
    }

    /**
     * {@inheritDoc}
     */
    public function getPublicExcludes(): array
    {
        return $this->getOptions()->getPublicExcludes();
    }

    /**
     * {@inheritDoc}
     */
    public function getIndexStubPath(): string
    {
        return $this->getOptions()->getIndexStubPath();
    }

    /**
     * {@inheritDoc}
     */
    public function getConstantsStubPath(): string
    {
        return $this->getOptions()->getConstantsStubPath();
    }

    /**
     * {@inheritDoc}
     */
    public function getRemoteLaravelPath(): string
    {
        return $this->getOptions()->getRemoteLaravelPath();
    }

    /**
     * {@inheritDoc}
     */
    public function getRemotePublicPath(): string
    {
        return $this->getOptions()->getRemotePublicPath();
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
