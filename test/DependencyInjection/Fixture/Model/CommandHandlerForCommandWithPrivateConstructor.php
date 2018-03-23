<?php

declare(strict_types=1);

namespace ProophTest\Bundle\ServiceBus\DependencyInjection\Fixture\Model;

class CommandHandlerForCommandWithPrivateConstructor
{
    public function handle(CommandWithPrivateConstructor $command): void
    {
    }
}
