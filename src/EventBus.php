<?php

declare(strict_types=1);

namespace Prooph\Bundle\ServiceBus;

use Prooph\ServiceBus\EventBus as BaseEventBus;

class EventBus extends BaseEventBus implements NamedMessageBus
{
    use NamedMessageBusTrait;
}
