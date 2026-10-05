<?php

namespace SameOldNick\LaravelSuitcase\Exceptions;

class ExportDirectoryNotCreated extends \RuntimeException
{
    public function __construct(string $exportPath)
    {
        parent::__construct("The export directory was not created: {$exportPath}");
    }
}
