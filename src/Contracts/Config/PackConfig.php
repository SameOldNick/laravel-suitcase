<?php

namespace SameOldNick\LaravelSuitcase\Contracts\Config;

interface PackConfig extends Options
{
    /**
     * Validates the configuration.
     *
     * @throws \Exception If validation fails.
     */
    public function validate(): void;
}
