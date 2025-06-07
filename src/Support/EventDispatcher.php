<?php

namespace SameOldNick\LaraHostPack\Support;

use SameOldNick\LaraHostPack\Contracts\PackConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;

class EventDispatcher
{
    public function __construct(
        protected readonly Command $command,
        protected readonly PackConfig $config
    ) {}

    public function dispatch(string $event, array $payload = []): void
    {
        Event::dispatch($event, [...$payload, 'command' => $this->command, 'config' => $this->config]);
    }
}
