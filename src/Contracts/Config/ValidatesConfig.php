<?php

namespace SameOldNick\LaravelSuitcase\Contracts\Config;

use SameOldNick\LaravelSuitcase\Config\PackConfig;

interface ValidatesConfig
{
    public function validate(PackConfigContract $config): void;
    public function validate(PackConfig $config): void;
}
