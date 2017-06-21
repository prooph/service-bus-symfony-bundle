<?php

declare(strict_types=1);

namespace ProophTest\Bundle\ServiceBus\DependencyInjection\Fixture\Model;

use Prooph\Common\Messaging\Message;
use Prooph\ServiceBus\Async\MessageProducer;
use React\Promise\Deferred;

class AsyncMessageProducer implements MessageProducer
{
    public function __invoke(Message $message, Deferred $deferred = null): void
    {
    }
}
