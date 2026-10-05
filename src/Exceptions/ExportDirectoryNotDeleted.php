<?php

namespace SameOldNick\LaravelSuitcase\Exceptions;

class ExportDirectoryNotDeleted extends \RuntimeException
{
    public function __construct(string $exportPath)
    {
        parent::__construct("The export directory was not deleted: {$exportPath}");
    }
}
