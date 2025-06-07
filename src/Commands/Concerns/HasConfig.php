<?php

namespace SameOldNick\LaraHostPack\Commands\Concerns;

use SameOldNick\LaraHostPack\Contracts\PackConfig;

/**
 * @mixin \SameOldNick\LaraHostPack\Commands\PackForSharedHosting
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
         * @var \SameOldNick\LaraHostPack\Commands\PackForSharedHosting $this
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
         * @var \SameOldNick\LaraHostPack\Commands\PackForSharedHosting $this
         */

        return $this->config;
    }

    /**
     * Validate the configuration.
     */
    protected function validateConfig(): bool
    {
        /**
         * @var \SameOldNick\LaraHostPack\Commands\PackForSharedHosting $this
         */
        try {
            $this->config->validate();
        } catch (\InvalidArgumentException $e) {
            $this->error('Configuration error: ' . $e->getMessage());

            return false;
        }

        return true;
    }
}
