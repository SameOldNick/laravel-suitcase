<?php

namespace SameOldNick\LaravelSuitcase\Runners;

use SameOldNick\LaravelSuitcase\Contracts\Config\PackConfig;
use SameOldNick\LaravelSuitcase\Contracts\EnvVariables;
use SameOldNick\LaravelSuitcase\Contracts\Outputter;
use SameOldNick\LaravelSuitcase\Support\EventDispatcher;

class PackPipelineContext
{
    /**
     * Create a new instance of the PackPipelineContext.
     *
     * @param  PackConfig  $config  The configuration for the packaging process.
     * @param  EnvVariables  $envVariables  The environment variables to be used during packaging.
     * @param  Outputter  $outputter  The outputter for logging messages during the packaging process.
     * @param  EventDispatcher|null  $eventDispatcher  The event dispatcher for dispatching events during the packaging process.
     */
    public function __construct(
        protected readonly PackConfig $config,
        protected readonly EnvVariables $envVariables,
        protected readonly Outputter $outputter,
        protected readonly ?EventDispatcher $eventDispatcher
    ) {
        //
    }

    /**
     * Get the configuration for the packaging process.
     *
     * @return PackConfig The configuration for the packaging process.
     */
    public function getConfig(): PackConfig
    {
        return $this->config;
    }

    /**
     * Get the environment variables for the packaging process.
     *
     * @return EnvVariables The environment variables for the packaging process.
     */
    public function getEnvVariables(): EnvVariables
    {
        return $this->envVariables;
    }

    /**
     * Get the outputter for logging messages during the packaging process.
     *
     * @return Outputter The outputter for logging messages.
     */
    public function getOutputter(): Outputter
    {
        return $this->outputter;
    }

    /**
     * Get the event dispatcher for dispatching events during the packaging process.
     *
     * @return EventDispatcher|null The event dispatcher for dispatching events, or null if not provided.
     */
    public function getEventDispatcher(): ?EventDispatcher
    {
        return $this->eventDispatcher;
    }
}
