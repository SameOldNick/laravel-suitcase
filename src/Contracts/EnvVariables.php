<?php

namespace SameOldNick\LaravelSuitcase\Contracts;

interface EnvVariables
{
    /**
     * Get the customized environment variables for the packaging process.
     *
     * @return array The customized environment variables.
     */
    public function getCustomizedVariables(): array;
}
