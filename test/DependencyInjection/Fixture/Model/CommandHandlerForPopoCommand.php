<?php

declare(strict_types=1);

namespace ProophTest\Bundle\ServiceBus\DependencyInjection\Fixture\Model;

final class CommandHandlerForPopoCommand
{
    /** @var PopoCommand|null */
    public $lastCommand;

    public function __invoke(PopoCommand $command)
    {
        $this->lastCommand = $command;
    }

    /**
     * Do not take notice of this method.
     * It is necessary to run the tests against prooph/service-bus:6.0.0
     * @see https://github.com/prooph/service-bus/pull/159
     */
    public function handle(): void
    {
    }
}
