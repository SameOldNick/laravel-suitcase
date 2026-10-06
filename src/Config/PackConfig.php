<?php

namespace SameOldNick\LaravelSuitcase\Config;

use SameOldNick\LaravelSuitcase\Contracts\Config\Options;
use SameOldNick\LaravelSuitcase\Contracts\Config\ValidatesConfig;

/**
 * Class PackConfig
 *
 * Holds the resolved configuration options for a packaging run together with the
 * validator that checks them. Every accessor delegates to the underlying options.
 */
class PackConfig
{
    /**
     * Create a new PackConfig instance.
     *
     * @param  Options  $options  The configuration options to read from.
     * @param  ValidatesConfig  $validator  The validator used to check the options.
     */
    public function __construct(
        protected readonly Options $options,
        protected readonly ValidatesConfig $validator
    ) {
        //
    }

    /**
     * Validate the configuration.
     *
     * @throws \InvalidArgumentException if the configuration is invalid.
     */
    public function validate(): void
    {
        $this->getValidator()->validate($this);
    }

    /**
     * Gets the absolute export directory path.
     */
    public function getExportPath(): string
    {
        return $this->getOptions()->getExportPath();
    }

    /**
     * Gets the absolute path to the exported public_html directory.
     */
    public function getPublicPath(): string
    {
        return $this->getOptions()->getPublicPath();
    }

    /**
     * Gets the absolute path to the exported Laravel directory.
     */
    public function getLaravelPath(): string
    {
        return $this->getOptions()->getLaravelPath();
    }

    /**
     * Gets the absolute path to the generated zip file.
     */
    public function getZipPath(): string
    {
        return $this->getOptions()->getZipPath();
    }

    /**
     * Gets the absolute path to the .env file to be used for export.
     */
    public function getEnvFilePath(): string
    {
        return $this->getOptions()->getEnvFilePath();
    }

    /**
     * Checks if database dump is enabled.
     */
    public function getDbDumpEnabled(): bool
    {
        return $this->getOptions()->getDbDumpEnabled();
    }

    /**
     * Gets the database connections to dump.
     *
     * @return array<string, mixed>
     */
    public function getDbConnections(): array
    {
        return $this->getOptions()->getDbConnections();
    }

    /**
     * Gets a list of files to include at the root of the export.
     *
     * @return array<int, string>
     */
    public function getRootFiles(): array
    {
        return $this->getOptions()->getRootFiles();
    }

    /**
     * Gets a list of files/directories to include in the Laravel export.
     *
     * @return array<int, string>
     */
    public function getLaravelIncludes(): array
    {
        return $this->getOptions()->getLaravelIncludes();
    }

    /**
     * Gets a list of files/directories to include in the public_html export.
     *
     * @return array<int, string>
     */
    public function getPublicIncludes(): array
    {
        return $this->getOptions()->getPublicIncludes();
    }

    /**
     * Gets a list of files/directories to exclude from the Laravel export.
     *
     * @return array<int, string>
     */
    public function getLaravelExcludes(): array
    {
        return $this->getOptions()->getLaravelExcludes();
    }

    /**
     * Gets a list of files/directories to exclude from the public_html export.
     *
     * @return array<int, string>
     */
    public function getPublicExcludes(): array
    {
        return $this->getOptions()->getPublicExcludes();
    }

    /**
     * Gets the absolute path to the index.php stub file.
     */
    public function getIndexStubPath(): string
    {
        return $this->getOptions()->getIndexStubPath();
    }

    /**
     * Gets the absolute path to the constants.php stub file.
     */
    public function getConstantsStubPath(): string
    {
        return $this->getOptions()->getConstantsStubPath();
    }

    /**
     * Gets the path to the remote Laravel directory on the shared hosting server.
     */
    public function getRemoteLaravelPath(): string
    {
        return $this->getOptions()->getRemoteLaravelPath();
    }

    /**
     * Gets the path to the remote public_html directory on the shared hosting server.
     */
    public function getRemotePublicPath(): string
    {
        return $this->getOptions()->getRemotePublicPath();
    }

    /**
     * Checks if the .env file should be skipped during export.
     */
    public function shouldSkipEnv(): bool
    {
        return $this->getOptions()->shouldSkipEnv();
    }

    /**
     * Checks if the vendor directory should be skipped during export.
     */
    public function shouldSkipVendor(): bool
    {
        return $this->getOptions()->shouldSkipVendor();
    }

    /**
     * Gets the options instance.
     *
     * @return Options The configuration options in use.
     */
    public function getOptions(): Options
    {
        return $this->options;
    }

    /**
     * Gets the validator instance.
     *
     * @return ValidatesConfig The validator used to check the options.
     */
    public function getValidator(): ValidatesConfig
    {
        return $this->validator;
    }

    /**
     * Creates a new PackConfig instance from an Options instance.
     *
     * @param  Options  $options  The options to configure the package with.
     * @return self A configuration using the default validator.
     */
    public static function createWithDefaultValidator(Options $options): self
    {
        return new self($options, new Validators\DefaultValidator);
    }
}
