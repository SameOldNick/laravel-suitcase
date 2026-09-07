<?php

namespace SameOldNick\LaravelSuitcase\Config;

use SameOldNick\LaravelSuitcase\Contracts\Config\Options as OptionsContract;
use SameOldNick\LaravelSuitcase\Contracts\Config\Repository;

class Options implements OptionsContract
{
    public const DEFAULT_REMOTE_LARAVEL = '/home/username/laravel';

    public const DEFAULT_REMOTE_PUBLIC = '/home/username/public_html';

    public const INDEX_STUB = __DIR__.'/../../stubs/index.php.stub';

    public const CONSTANTS_STUB = __DIR__.'/../../stubs/constants.php.stub';

    public function __construct(
        protected readonly Repository $repository,
    ) {}

    // === Export & Deployment Paths ===

    /**
     * {@inheritDoc}
     */
    public function getIndexStubPath(): string
    {
        return static::INDEX_STUB;
    }

    /**
     * {@inheritDoc}
     */
    public function getConstantsStubPath(): string
    {
        return static::CONSTANTS_STUB;
    }

    /**
     * {@inheritDoc}
     */
    public function getRemoteLaravelPath(): string
    {
        return $this->repository->getOption('remote.laravel_path', static::DEFAULT_REMOTE_LARAVEL);
    }

    /**
     * {@inheritDoc}
     */
    public function getRemotePublicPath(): string
    {
        return $this->repository->getOption('remote.public_path', static::DEFAULT_REMOTE_PUBLIC);
    }

    /**
     * {@inheritDoc}
     */
    public function getExportPath(): string
    {
        return base_path($this->repository->getOption('export_dir'));
    }

    /**
     * {@inheritDoc}
     */
    public function getPublicPath(): string
    {
        return $this->getExportPath().'/public_html';
    }

    /**
     * {@inheritDoc}
     */
    public function getLaravelPath(): string
    {
        return $this->getExportPath().'/laravel';
    }

    /**
     * {@inheritDoc}
     */
    public function getZipPath(): string
    {
        return base_path($this->repository->getOption('zip_name'));
    }

    /**
     * {@inheritDoc}
     */
    public function getEnvFilePath(): string
    {
        return base_path($this->repository->getOption('env_file'));
    }

    // === Database Export Options ===

    /**
     * {@inheritDoc}
     */
    public function getDbDumpEnabled(): bool
    {
        return $this->repository->getOption('db_dump.enabled', false);
    }

    /**
     * {@inheritDoc}
     */
    public function getDbConnection(): string
    {
        return $this->repository->getOption('db_dump.connection', 'mysql');
    }

    /**
     * {@inheritDoc}
     */
    public function getDbDumpOptions(): array
    {
        return $this->repository->getOption('db_dump.extra_options', []);
    }

    // === File Inclusion/Exclusion ===

    /**
     * {@inheritDoc}
     */
    public function getRootFiles(): array
    {
        return $this->repository->getOption('root_files', []);
    }

    /**
     * {@inheritDoc}
     */
    public function getLaravelIncludes(): array
    {
        return $this->repository->getOption('include.laravel', []);
    }

    /**
     * {@inheritDoc}
     */
    public function getPublicIncludes(): array
    {
        return $this->repository->getOption('include.public', []);
    }

    /**
     * {@inheritDoc}
     */
    public function getLaravelExcludes(): array
    {
        return $this->repository->getOption('exclude.laravel', []);
    }

    /**
     * {@inheritDoc}
     */
    public function getPublicExcludes(): array
    {
        return $this->repository->getOption('exclude.public', []);
    }
}
