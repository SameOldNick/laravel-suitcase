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
     * @inheritDoc
     */
    public function getOption(string $key, $default = null): mixed
    {
        return $this->configRepository->get(static::CONFIG_ROOT_KEY . "." . $key, $default);
    }

    /**
     * @inheritDoc
     */
    public function all(): array
    {
        return $this->configRepository->get(static::CONFIG_ROOT_KEY, []);
    }
}
