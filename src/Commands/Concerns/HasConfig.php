<?php

namespace SameOldNick\LaravelSuitcase\Commands\Concerns;

use SameOldNick\LaravelSuitcase\Commands\PackForSharedHosting;
use SameOldNick\LaravelSuitcase\Contracts\Config\PackConfig;

/**
 * Provides methods for handling configuration in commands that implement the PackForSharedHosting interface.
 *
 * @mixin PackForSharedHosting
 */
trait HasConfig
{
    private PackConfig $config;

    /**
     * Set the configuration for the command.
     */
    protected function setConfig(PackConfig $config): static
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Get the configuration for the command.
     */
    protected function getConfig(): PackConfig
    {
        return $this->config;
    }

    /**
     * Validate the configuration.
     */
    protected function validateConfig(): bool
    {
        try {
            $this->config->validate();
        } catch (\InvalidArgumentException $e) {
            $this->error('Configuration error: '.$e->getMessage());

            return false;
        }

        return true;
    }
}
