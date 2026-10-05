<?php

namespace SameOldNick\LaravelSuitcase\Exceptions;

class ZipFileNotWritable extends \RuntimeException
{
    public function __construct(string $zipPath)
    {
        parent::__construct("The ZIP file is not writable: {$zipPath}");
    }
}
