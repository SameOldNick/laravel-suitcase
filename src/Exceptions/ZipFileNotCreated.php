<?php

namespace SameOldNick\LaravelSuitcase\Exceptions;

class ZipFileNotCreated extends \RuntimeException
{
    public function __construct(string $zipPath)
    {
        parent::__construct("The ZIP file was not created: {$zipPath}");
    }
}
