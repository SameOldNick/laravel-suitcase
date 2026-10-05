<?php

namespace SameOldNick\LaravelSuitcase\Config;

use Illuminate\Container\Attributes\Config;
use SameOldNick\LaravelSuitcase\Contracts\Config\Options as OptionsContract;

class Options implements OptionsContract
{
    public const CONFIG_ROOT_KEY = 'suitcase';

    public const DEFAULT_REMOTE_LARAVEL = '/home/username/laravel';

    public const DEFAULT_REMOTE_PUBLIC = '/home/username/public_html';

    public const INDEX_STUB = __DIR__.'/../../stubs/index.php.stub';

    public const CONSTANTS_STUB = __DIR__.'/../../stubs/constants.php.stub';

    /**
     * Constructs Options instance
     */
    public function __construct(
        #[Config('suitcase.remote.laravel_path', self::DEFAULT_REMOTE_LARAVEL)]
        protected readonly string $remoteLaravelPath,
        #[Config('suitcase.remote.public_path', self::DEFAULT_REMOTE_PUBLIC)]
        protected readonly string $remotePublicPath,
        #[Config('suitcase.export_dir', '')]
        protected readonly string $exportPath,
        #[Config('suitcase.zip_name', '')]
        protected readonly string $zipPath,
        #[Config('suitcase.env_file', '')]
        protected readonly string $envFilePath,
        #[Config('suitcase.db_dump.enabled', false)]
        protected readonly bool $dbDumpEnabled,
        #[Config('suitcase.db_dump.connections', [])]
        protected readonly array $dbConnections,
        #[Config('suitcase.root_files', [])]
        protected readonly array $rootFiles,
        #[Config('suitcase.include.laravel', [])]
        protected readonly array $laravelIncludes,
        #[Config('suitcase.include.public', [])]
        protected readonly array $publicIncludes,
        #[Config('suitcase.exclude.laravel', [])]
        protected readonly array $laravelExcludes,
        #[Config('suitcase.exclude.public', [])]
        protected readonly array $publicExcludes,
        #[Config('suitcase.stubs.index', self::INDEX_STUB)]
        protected readonly string $indexStub,
        #[Config('suitcase.stubs.constants', self::CONSTANTS_STUB)]
        protected readonly string $constantsStub,
        protected readonly ?string $publicPath = null,
        protected readonly ?string $laravelPath = null,
    ) {
        //
    }

    // === Export & Deployment Paths ===

    /**
     * {@inheritDoc}
     */
    public function getIndexStubPath(): string
    {
        return $this->indexStub;
    }

    /**
     * {@inheritDoc}
     */
    public function getConstantsStubPath(): string
    {
        return $this->constantsStub;
    }

    /**
     * {@inheritDoc}
     */
    public function getRemoteLaravelPath(): string
    {
        return $this->remoteLaravelPath;
    }

    /**
     * {@inheritDoc}
     */
    public function getRemotePublicPath(): string
    {
        return $this->remotePublicPath;
    }

    /**
     * {@inheritDoc}
     */
    public function getExportPath(): string
    {
        return base_path($this->exportPath);
    }

    /**
     * {@inheritDoc}
     */
    public function getPublicPath(): string
    {
        return $this->publicPath ?? $this->getExportPath().'/public_html';
    }

    /**
     * {@inheritDoc}
     */
    public function getLaravelPath(): string
    {
        return $this->laravelPath ?? $this->getExportPath().'/laravel';
    }

    /**
     * {@inheritDoc}
     */
    public function getZipPath(): string
    {
        return base_path($this->zipPath);
    }

    /**
     * {@inheritDoc}
     */
    public function getEnvFilePath(): string
    {
        return base_path($this->envFilePath);
    }

    // === Database Export Options ===

    /**
     * {@inheritDoc}
     */
    public function getDbDumpEnabled(): bool
    {
        return $this->dbDumpEnabled;
    }

    /**
     * {@inheritDoc}
     */
    public function getDbConnections(): array
    {
        return $this->dbConnections;
    }

    // === File Inclusion/Exclusion ===

    /**
     * {@inheritDoc}
     */
    public function getRootFiles(): array
    {
        return $this->rootFiles;
    }

    /**
     * {@inheritDoc}
     */
    public function getLaravelIncludes(): array
    {
        return $this->laravelIncludes;
    }

    /**
     * {@inheritDoc}
     */
    public function getPublicIncludes(): array
    {
        return $this->publicIncludes;
    }

    /**
     * {@inheritDoc}
     */
    public function getLaravelExcludes(): array
    {
        return $this->laravelExcludes;
    }

    /**
     * {@inheritDoc}
     */
    public function getPublicExcludes(): array
    {
        return $this->publicExcludes;
    }
}
