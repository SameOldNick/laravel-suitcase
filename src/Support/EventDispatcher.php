<?php

namespace SameOldNick\LaravelSuitcase\Support;

use Illuminate\Support\Facades\Event;
use SameOldNick\LaravelSuitcase\Contracts\Config\PackConfig;

class EventDispatcher
{
    public function __construct(
        protected readonly PackConfig $config,
    ) {}

    public function dispatch(string $event, array $payload = []): void
    {
        Event::dispatch($event, [...$payload, 'config' => $this->config]);
    }
}
