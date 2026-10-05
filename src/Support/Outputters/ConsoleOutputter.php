<?php

namespace SameOldNick\LaravelSuitcase\Support\Outputters;

use Illuminate\Console\OutputStyle;
use SameOldNick\LaravelSuitcase\Contracts\Outputter;
use Symfony\Component\Console\Helper\ProgressBar;

class ConsoleOutputter implements Outputter
{
    /**
     * Create a new ConsoleOutputter instance.
     *
     * @param  OutputStyle  $output  The output style instance for console output.
     */
    public function __construct(
        protected readonly OutputStyle $output,
    ) {
        //
    }

    /**
     * {@inheritDoc}
     */
    public function info(string $message): void
    {
        $this->output->info($message);
    }

    /**
     * {@inheritDoc}
     */
    public function error(string $message): void
    {
        $this->output->error($message);
    }

    /**
     * {@inheritDoc}
     */
    public function success(string $message): void
    {
        $this->output->success($message);
    }

    /**
     * {@inheritDoc}
     */
    public function warning(string $message): void
    {
        $this->output->warning($message);
    }

    /**
     * {@inheritDoc}
     */
    public function newLine(int $count = 1): void
    {
        $this->output->newLine($count);
    }

    /**
     * {@inheritDoc}
     */
    public function createProgressBar(int $max = 0): ProgressBar
    {
        return $this->output->createProgressBar($max);
    }
}
