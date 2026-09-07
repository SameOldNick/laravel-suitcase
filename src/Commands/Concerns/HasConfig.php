<?php

namespace SameOldNick\LaravelSuitcase\Commands\Concerns;

use SameOldNick\LaravelSuitcase\Contracts\Config\PackConfig;

/**
 * @mixin \SameOldNick\LaravelSuitcase\Commands\PackForSharedHosting
 */
trait HasConfig
{
    private PackConfig $config;

    /**
     * Set the configuration for the command.
     */
    protected function setConfig(PackConfig $config): static
    {
        /**
         * @var \SameOldNick\LaravelSuitcase\Commands\PackForSharedHosting $this
         */
        $this->config = $config;

        return $this;
    }

    /**
     * Get the configuration for the command.
     */
    protected function getConfig(): PackConfig
    {
        /**
         * @var \SameOldNick\LaravelSuitcase\Commands\PackForSharedHosting $this
         */

        return $this->config;
    }

    /**
     * Validate the configuration.
     */
    protected function validateConfig(): bool
    {
        /**
         * @var \SameOldNick\LaravelSuitcase\Commands\PackForSharedHosting $this
         */
        try {
            $this->config->validate();
        } catch (\InvalidArgumentException $e) {
            $this->error('Configuration error: '.$e->getMessage());

            return false;
        }

        return true;
    }
}
