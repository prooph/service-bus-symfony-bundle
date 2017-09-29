<?php

declare(strict_types=1);

namespace Prooph\Bundle\ServiceBus;

use Prooph\ServiceBus\CommandBus as BaseCommandBus;

class CommandBus extends BaseCommandBus implements NamedMessageBus
{
    use NamedMessageBusTrait;
}
