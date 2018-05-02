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
}
