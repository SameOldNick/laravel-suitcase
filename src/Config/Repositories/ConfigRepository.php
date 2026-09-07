<?php

namespace SameOldNick\LaravelSuitcase\Config\Repositories;

use Illuminate\Contracts\Config\Repository as LaravelConfigRepository;
use SameOldNick\LaravelSuitcase\Contracts\Config\Repository;

class ConfigRepository implements Repository
{
    const CONFIG_ROOT_KEY = 'suitcase';

    public function __construct(
        protected readonly LaravelConfigRepository $configRepository,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function getOption(string $key, $default = null): mixed
    {
        return $this->getLaravelRepository()->get(static::CONFIG_ROOT_KEY.'.'.$key, $default);
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
     */
    public function getLaravelRepository(): LaravelConfigRepository
    {
        return $this->configRepository;
    }

    /**
     * Get the root key for the Laravel Suitcase configuration.
     */
    public static function getConfigRootKey(): string
    {
        return static::CONFIG_ROOT_KEY;
    }
}
