<?php

declare(strict_types=1);

namespace ProophTest\Bundle\ServiceBus\DependencyInjection\Fixture\Model;

use Prooph\Common\Messaging\Command;
use Prooph\Common\Messaging\PayloadTrait;

class CommandWithPrivateConstructor extends Command
{
    use PayloadTrait;

    public static function create(): self
    {
        return new self();
    }

    private function __construct()
    {
        $this->init();
        $this->setPayload([]);
    }
}
