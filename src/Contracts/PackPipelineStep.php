<?php

namespace SameOldNick\LaravelSuitcase\Contracts;

use SameOldNick\LaravelSuitcase\Runners\PackPipelineContext;

interface PackPipelineStep
{
    /**
     * Execute the step in the pipeline.
     *
     * @param  PackPipelineContext  $context  The context of the pipeline.
     */
    public function __invoke(PackPipelineContext $context);
}
