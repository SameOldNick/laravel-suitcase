<?php

namespace SameOldNick\LaraHostPack\Support;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;
use SameOldNick\LaraHostPack\Contracts\Config\PackConfig;

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
