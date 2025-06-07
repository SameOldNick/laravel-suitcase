<?php

namespace SameOldNick\LaraHostPack\Support;

use SameOldNick\LaraHostPack\Contracts\EnvVariables as EnvVariablesContract;
use Illuminate\Encryption\Encrypter;

class EnvVariables implements EnvVariablesContract
{
    public function getDefaultVariables(): array
    {
        return [
            'APP_KEY' => 'base64:' . base64_encode(Encrypter::generateKey(config('app.cipher'))),
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
            'SHARED_HOSTING' => 'true',
        ];
    }

    public function getCustomizedVariables(): array
    {
        $variables = $this->getDefaultVariables();

        // Dispatch an event to allow customization
        event('hostpack.env.variables', ['variables' => &$variables]);

        return $variables;
    }
}
