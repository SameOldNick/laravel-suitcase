<?php

namespace SameOldNick\LaraHostPack\Contracts\Config;

interface Repository
{
    /**
     * Gets the value of a configuration option.
     *
     * @param string $key The configuration key.
     * @param mixed $default The default value if the key does not exist.
     * @return mixed
     */
    public function getOption(string $key, $default = null): mixed;

    /**
     * Gets all configuration options.
     *
     * @return array
     */
    public function all(): array;
}
