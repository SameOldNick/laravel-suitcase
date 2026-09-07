<?php

namespace SameOldNick\LaravelSuitcase\Contracts\Config;

interface Repository
{
    /**
     * Gets the value of a configuration option.
     *
     * @param  string  $key  The configuration key.
     * @param  mixed  $default  The default value if the key does not exist.
     */
    public function getOption(string $key, $default = null): mixed;

    /**
     * Gets all configuration options.
     */
    public function all(): array;
}
