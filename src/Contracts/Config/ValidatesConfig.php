<?php

namespace SameOldNick\LaraHostPack\Contracts\Config;

use SameOldNick\LaraHostPack\Contracts\Config\PackConfig as PackConfigContract;

interface ValidatesConfig
{
    public function validate(PackConfigContract $config): void;
}
