<?php

namespace SameOldNick\LaraHostPack\Config\Repositories;

use Illuminate\Contracts\Config\Repository as LaravelConfigRepository;
use SameOldNick\LaraHostPack\Contracts\Config\Repository;

class ConfigRepository implements Repository
{
    const CONFIG_ROOT_KEY = 'larahostpack';

    public function __construct(
        protected readonly LaravelConfigRepository $configRepository,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function getOption(string $key, $default = null): mixed
    {
        return $this->getLaravelRepository()->get(static::CONFIG_ROOT_KEY . '.' . $key, $default);
    }

    /**
     * {@inheritDoc}
     */
    public function all(): array
    {
        return $this->getLaravelRepository()->get(static::CONFIG_ROOT_KEY, []);
    }

    /**
     * Get the underlying Laravel config repository instance.
     *
     * @return LaravelConfigRepository
     */
    public function getLaravelRepository(): LaravelConfigRepository
    {
        return $this->configRepository;
    }

    /**
     * Get the root key for the LaraHostPack configuration.
     *
     * @return string
     */
    public static function getConfigRootKey(): string
    {
        return static::CONFIG_ROOT_KEY;
    }
}
