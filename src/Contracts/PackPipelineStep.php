<?php

namespace SameOldNick\LaravelSuitcase\Contracts;

use SameOldNick\LaravelSuitcase\Runners\PackPipelineContext;

interface PackPipelineStep
{
    /**
     * Perform the step in the pipeline.
     *
     * @param  PackPipelineContext  $context  The context of the pipeline.
     */
    public function perform(PackPipelineContext $context);
}
