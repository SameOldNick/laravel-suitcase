<?php

namespace SameOldNick\LaravelSuitcase\Contracts;

use Symfony\Component\Console\Helper\ProgressBar;

interface Outputter
{
    /**
     * Output an success message.
     */
    public function success(string $message): void;

    /**
     * Output a warning message.
     */
    public function warning(string $message): void;

    /**
     * Output an info message.
     */
    public function info(string $message): void;

    /**
     * Output an error message.
     */
    public function error(string $message): void;

    /**
     * Output a new line.
     */
    public function newLine(int $count = 1): void;

    /**
     * Create a progress bar.
     *
     * @param  int  $max  The maximum number of steps for the progress bar.
     * @return ProgressBar|null The created progress bar, or null if not supported.
     */
    public function createProgressBar(int $max = 0): ?ProgressBar;
}
