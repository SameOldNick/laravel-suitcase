<?php

namespace SameOldNick\LaraHostPack\Contracts;

interface PackConfig
{
    public function validate(): void;

    public function getExportPath(): string;

    public function getPublicPath(): string;

    public function getLaravelPath(): string;

    public function getZipPath(): string;

    public function getEnvFilePath(): string;

    public function getDbDumpEnabled(): bool;

    public function getDbConnection(): string;

    public function getDbDumpOptions(): array;

    public function getRootFiles(): array;

    public function getLaravelIncludes(): array;

    public function getPublicIncludes(): array;

    public function getLaravelExcludes(): array;

    public function getPublicExcludes(): array;

    public function getIndexStubPath(): string;

    public function getConstantsStubPath(): string;

    /**
     * Gets the path to the remote Laravel directory on the shared hosting server.
     */
    public function getRemoteLaravelPath(): string;

    /**
     * Gets the path to the remote public_html directory on the shared hosting server.
     */
    public function getRemotePublicPath(): string;

    public function getOption(string $key, $default = null): mixed;
}
