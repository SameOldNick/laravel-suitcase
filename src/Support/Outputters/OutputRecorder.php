<?php

namespace SameOldNick\LaravelSuitcase\Support\Outputters;

use SameOldNick\LaravelSuitcase\Contracts\Outputter;
use Symfony\Component\Console\Helper\ProgressBar;

class OutputRecorder implements Outputter
{
    protected array $messages = [];

    /**
     * {@inheritDoc}
     */
    public function info(string $message): void
    {
        $this->recordMessage('info', $message);
    }

    /**
     * {@inheritDoc}
     */
    public function error(string $message): void
    {
        $this->recordMessage('error', $message);
    }

    /**
     * {@inheritDoc}
     */
    public function success(string $message): void
    {
        $this->recordMessage('success', $message);
    }

    /**
     * {@inheritDoc}
     */
    public function warning(string $message): void
    {
        $this->recordMessage('warning', $message);
    }

    /**
     * {@inheritDoc}
     */
    public function newLine(int $count = 1): void
    {
        for ($i = 0; $i < $count; $i++) {
            $this->recordMessage('newline', '');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function createProgressBar(int $max = 0): ?ProgressBar
    {
        return null;
    }

    /**
     * Get the recorded messages, in the order they were emitted.
     *
     * @return array<int, array{type: string, message: string}>
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * Record a message with its type.
     *
     * @param  string  $type  The type of the message (info, error, success, warning, newline).
     * @param  string  $message  The message content.
     */
    protected function recordMessage(string $type, string $message): void
    {
        $this->messages[] = [
            'type' => $type,
            'message' => $message,
        ];
    }
}
