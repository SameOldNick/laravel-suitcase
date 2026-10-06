<?php

namespace SameOldNick\LaravelSuitcase\Contracts\Config;

use SameOldNick\LaravelSuitcase\Config\PackConfig;

interface ValidatesConfig
{
    /**
     * Validate the given PackConfig instance.
     *
     * @param  PackConfig  $config  The PackConfig instance to validate.
     *
     * @throws \InvalidArgumentException if the configuration is invalid.
     */
    public function validate(PackConfig $config): void;
}
