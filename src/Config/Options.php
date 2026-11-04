<?php

namespace SameOldNick\LaravelSuitcase\Config;

use Illuminate\Container\Attributes\Config;
use SameOldNick\LaravelSuitcase\Contracts\Config\Options as OptionsContract;

readonly class Options implements OptionsContract
{
    public const CONFIG_ROOT_KEY = 'suitcase';

    public const DEFAULT_REMOTE_LARAVEL = '/home/username/laravel';

    public const DEFAULT_REMOTE_PUBLIC = '/home/username/public_html';

    public const INDEX_STUB = __DIR__.'/../../stubs/index.php.stub';

    public const CONSTANTS_STUB = __DIR__.'/../../stubs/constants.php.stub';

    /**
     * Path to the public directory on the shared hosting server
     */
    protected string $publicPath;

    /**
     * Path to the Laravel directory on the shared hosting server
     */
    protected string $laravelPath;

    /**
     * Constructs Options instance
     */
    public function __construct(
        #[Config('suitcase.remote.laravel_path', self::DEFAULT_REMOTE_LARAVEL)]
        protected string $remoteLaravelPath,
        #[Config('suitcase.remote.public_path', self::DEFAULT_REMOTE_PUBLIC)]
        protected string $remotePublicPath,
        #[Config('suitcase.export_dir', '')]
        protected string $exportPath,
        #[Config('suitcase.zip_name', '')]
        protected string $zipPath,
        #[Config('suitcase.env_file', '')]
        protected string $envFilePath,
        #[Config('suitcase.db_dump.enabled', false)]
        protected bool $dbDumpEnabled,
        #[Config('suitcase.db_dump.connections', [])]
        protected array $dbConnections,
        #[Config('suitcase.root_files', [])]
        protected array $rootFiles,
        #[Config('suitcase.include.laravel', [])]
        protected array $laravelIncludes,
        #[Config('suitcase.include.public', [])]
        protected array $publicIncludes,
        #[Config('suitcase.exclude.laravel', [])]
        protected array $laravelExcludes,
        #[Config('suitcase.exclude.public', [])]
        protected array $publicExcludes,
        #[Config('suitcase.stubs.index', self::INDEX_STUB)]
        protected string $indexStub,
        #[Config('suitcase.stubs.constants', self::CONSTANTS_STUB)]
        protected string $constantsStub,
        #[Config('suitcase.export.public_path', null)]
        ?string $publicPath,
        #[Config('suitcase.export.laravel_path', null)]
        ?string $laravelPath,
        #[Config('suitcase.skip.vendor', false)]
        protected bool $skipVendor,
        #[Config('suitcase.skip.env', false)]
        protected bool $skipEnv,
    ) {
        $this->publicPath = $publicPath ?? $this->getExportPath().'/public_html';
        $this->laravelPath = $laravelPath ?? $this->getExportPath().'/laravel';
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
        return $this->publicPath;
    }

    /**
     * {@inheritDoc}
     */
    public function getLaravelPath(): string
    {
        return $this->laravelPath;
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

    /**
     * {@inheritDoc}
     */
    public function shouldSkipEnv(): bool
    {
        return $this->skipEnv;
    }

    /**
     * {@inheritDoc}
     */
    public function shouldSkipVendor(): bool
    {
        return $this->skipVendor;
    }

    /**
     * Creates a new PackConfig instance from a config file.
     *
     * @param  string  $filePath  Path to the config file
     *
     * @throws \InvalidArgumentException if the config file does not exist or is not readable
     */
    public static function createFromFile(string $filePath): self
    {
        $config = require $filePath;

        if (! is_array($config)) {
            throw new \InvalidArgumentException('Configuration file must return an array.');
        }

        return self::createFromArray($config);
    }

    /**
     * Creates a new PackConfig instance from an array.
     *
     * @param  array  $config  Configuration array
     */
    public static function createFromArray(array $config): self
    {
        return new self(
            remoteLaravelPath: $config['remote']['laravel_path'] ?? self::DEFAULT_REMOTE_LARAVEL,
            remotePublicPath: $config['remote']['public_path'] ?? self::DEFAULT_REMOTE_PUBLIC,
            exportPath: $config['export_dir'] ?? '',
            zipPath: $config['zip_name'] ?? '',
            envFilePath: $config['env_file'] ?? '',
            dbDumpEnabled: $config['db_dump']['enabled'] ?? false,
            dbConnections: $config['db_dump']['connections'] ?? [],
            rootFiles: $config['root_files'] ?? [],
            laravelIncludes: $config['include']['laravel'] ?? [],
            publicIncludes: $config['include']['public'] ?? [],
            laravelExcludes: $config['exclude']['laravel'] ?? [],
            publicExcludes: $config['exclude']['public'] ?? [],
            indexStub: $config['stubs']['index'] ?? self::INDEX_STUB,
            constantsStub: $config['stubs']['constants'] ?? self::CONSTANTS_STUB,
            publicPath: $config['export']['public_path'] ?? null,
            laravelPath: $config['export']['laravel_path'] ?? null,
            skipVendor: $config['skip']['vendor'] ?? false,
            skipEnv: $config['skip']['env'] ?? false,
        );
    }
}
