<?php

namespace SameOldNick\LaravelSuitcase\Support;

use Illuminate\Encryption\Encrypter;
use SameOldNick\LaravelSuitcase\Contracts\EnvVariables as EnvVariablesContract;

class EnvVariables implements EnvVariablesContract
{
    /**
     * Get the default environment variables for the packaging process.
     *
     * @return array The default environment variables.
     */
    public function getDefaultVariables(): array
    {
        return [
            'APP_KEY' => 'base64:'.base64_encode(Encrypter::generateKey(config('app.cipher'))),
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
            'SHARED_HOSTING' => 'true',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getCustomizedVariables(): array
    {
        $variables = $this->getDefaultVariables();

        // Dispatch an event to allow customization
        event('suitcase.env.variables', ['variables' => &$variables]);

        return $variables;
    }
}
