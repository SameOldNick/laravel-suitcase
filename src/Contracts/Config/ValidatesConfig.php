<?php

namespace SameOldNick\LaravelSuitcase\Contracts\Config;

use SameOldNick\LaravelSuitcase\Contracts\Config\PackConfig as PackConfigContract;

interface ValidatesConfig
{
    public function validate(PackConfigContract $config): void;
}
