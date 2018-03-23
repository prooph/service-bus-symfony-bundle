<?php

declare(strict_types=1);

namespace ProophTest\Bundle\ServiceBus\DependencyInjection\Fixture\Model;

class CommandHandlerForCommandWithPrivateConstructor
{
    public function __invoke(CommandWithPrivateConstructor $command): void
    {
    }
}
