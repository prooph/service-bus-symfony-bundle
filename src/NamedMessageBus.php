<?php

declare(strict_types=1);

namespace Prooph\Bundle\ServiceBus;

use Prooph\ServiceBus\Plugin\Plugin;

interface NamedMessageBus
{
    public function busName(): string;

    public function busType(): string;

    public function addPlugin(Plugin $plugin, string $serviceId): void;

    public function plugins(): array;
}
