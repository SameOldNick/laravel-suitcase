<?php

namespace SameOldNick\LaravelSuitcase\Runners;

use Illuminate\Pipeline\Pipeline;
use SameOldNick\LaravelSuitcase\Contracts\Config\PackConfig;
use SameOldNick\LaravelSuitcase\Contracts\EnvVariables;
use SameOldNick\LaravelSuitcase\Contracts\Outputter;
use SameOldNick\LaravelSuitcase\Contracts\PackPipelineStep;
use SameOldNick\LaravelSuitcase\Support\EventDispatcher;

class PackForSharedHostingRunner
{
    /**
     * Create a new PackForSharedHostingRunner instance.
     *
     * @param  Outputter  $outputter  The outputter to use for console output.
     * @param  EnvVariables  $envVariables  The environment variables to use for the packaging process.
     * @param  EventDispatcher|null  $eventDispatcher  The event dispatcher to use for dispatching events (optional).
     */
    public function __construct(
        protected readonly Outputter $outputter,
        protected readonly EnvVariables $envVariables,
        protected readonly ?EventDispatcher $eventDispatcher
    ) {
        //
    }

    /**
     * Run the packaging process for shared hosting.
     *
     * @param  PackConfig  $config  The configuration for the packaging process.
     */
    public function run(PackConfig $config)
    {
        $context = $this->createContext($config);

        app(Pipeline::class)
            ->send($context)
            ->through($this->createPipelineSteps())
            ->then(function (PackPipelineContext $context) {
                $context->getOutputter()->newLine();
                $context->getOutputter()->success('✅ Package created successfully: '.$context->getConfig()->getZipPath());

                $context->getEventDispatcher()?->dispatch('suitcase.completed');

                $context->getOutputter()->info('To deploy your app, follow the instructions in the INSTALL.txt file.');

                $context->getOutputter()->newLine();
            });

    }

    /**
     * Create the pipeline context for the packaging process.
     *
     * @param  PackConfig  $config  The configuration for the packaging process.
     * @return PackPipelineContext The created pipeline context.
     */
    protected function createContext(PackConfig $config): PackPipelineContext
    {
        return new PackPipelineContext($config, $this->envVariables, $this->outputter, $this->eventDispatcher);
    }

    /**
     * Create the pipeline steps for the packaging process.
     *
     * @return array<int, callable> The array of pipeline steps.
     */
    protected function createPipelineSteps(): array
    {
        return array_map(fn ($step) => function ($context, \Closure $next) use ($step) {
            $stepInstance = app($step);

            $stepInstance($context);

            return $next($context);
        }, $this->getSteps());
    }

    /**
     * Get the list of pipeline steps for the packaging process.
     *
     * @return array<int, class-string<PackPipelineStep>> The array of pipeline step class names.
     */
    protected function getSteps(): array
    {
        return [
            Steps\PreparesZipFile::class,
            Steps\PreparesDirectories::class,
            Steps\DumpsDatabase::class,
            Steps\HandlesFileExport::class,
            Steps\PreparesFiles::class,
            Steps\HandlesZipping::class,
        ];
    }
}
