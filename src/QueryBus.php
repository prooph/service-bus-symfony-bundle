<?php

declare(strict_types=1);

namespace Prooph\Bundle\ServiceBus;

use Prooph\ServiceBus\QueryBus as BaseQueryBus;

class QueryBus extends BaseQueryBus implements NamedMessageBus
{
    use NamedMessageBusTrait;
}
