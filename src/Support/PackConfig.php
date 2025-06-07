<?php

namespace SameOldNick\LaraHostPack\Support;

use SameOldNick\LaraHostPack\Contracts\PackConfig as PackConfigContract;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

class PackConfig implements PackConfigContract
{
    protected readonly array $config;

    public function __construct()
    {
        $this->config = config('hostpack', []);
    }

    public function validate(): void
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

        if (! File::exists($this->getEnvFilePath())) {
            throw new \InvalidArgumentException('The specified .env file does not exist: ' . $this->getEnvFilePath());
        }

        if (! File::exists($this->getIndexStubPath())) {
            throw new \InvalidArgumentException("The index.php stub file does not exist: {$this->getIndexStubPath()}");
        }

        if (! File::exists($this->getConstantsStubPath())) {
            throw new \InvalidArgumentException("The constants.php stub file does not exist: {$this->getConstantsStubPath()}");
        }
    }

    public function getExportPath(): string
    {
        return base_path($this->getOption('export_dir'));
    }

    public function getPublicPath(): string
    {
        return $this->getExportPath() . '/public_html';
    }

    public function getLaravelPath(): string
    {
        return $this->getExportPath() . '/laravel';
    }

    public function getZipPath(): string
    {
        return base_path($this->getOption('zip_name'));
    }

    public function getEnvFilePath(): string
    {
        return base_path($this->getOption('env_file'));
    }

    public function getDbDumpEnabled(): bool
    {
        return $this->getOption('db_dump.enabled', false);
    }

    public function getDbConnection(): string
    {
        return $this->getOption('db_dump.connection', 'mysql');
    }

    public function getDbDumpOptions(): array
    {
        return $this->getOption('db_dump.extra_options', []);
    }

    public function getRootFiles(): array
    {
        return $this->getOption('root_files', []);
    }

    public function getLaravelIncludes(): array
    {
        return $this->getOption('include.laravel', []);
    }

    public function getPublicIncludes(): array
    {
        return $this->getOption('include.public', []);
    }

    public function getLaravelExcludes(): array
    {
        return $this->getOption('exclude.laravel', []);
    }

    public function getPublicExcludes(): array
    {
        return $this->getOption('exclude.public', []);
    }

    public function getIndexStubPath(): string
    {
        return __DIR__ . '/../../stubs/index.php.stub';
    }

    public function getConstantsStubPath(): string
    {
        return __DIR__ . '/../../stubs/constants.php.stub';
    }

    /**
     * {@inheritDoc}
     */
    public function getRemoteLaravelPath(): string
    {
        return $this->getOption('remote.laravel_path', '/home/username/laravel');
    }

    /**
     * {@inheritDoc}
     */
    public function getRemotePublicPath(): string
    {
        return $this->getOption('remote.public_path', '/home/username/public_html');
    }

    /**
     * Get an option from the configuration.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getOption(string $key, $default = null): mixed
    {
        return Arr::get($this->config, $key, $default);
    }
}
