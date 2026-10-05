<?php

namespace SameOldNick\LaravelSuitcase\Support;

use Illuminate\Support\Facades\Event;
use SameOldNick\LaravelSuitcase\Contracts\Config\PackConfig;

class EventDispatcher
{
    /**
     * Create a new EventDispatcher instance.
     *
     * @param  PackConfig  $config  The package configuration.
     */
    public function __construct(
        protected readonly PackConfig $config,
    ) {
        //
    }

    /**
     * Dispatch an event with the given name and payload.
     *
     * @param  string  $event  The name of the event to dispatch.
     * @param  array  $payload  The payload to include with the event (optional).
     */
    public function dispatch(string $event, array $payload = []): void
    {
        Event::dispatch($event, [...$payload, 'config' => $this->config]);
    }
}
