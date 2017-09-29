<?php

declare(strict_types=1);

namespace Prooph\Bundle\ServiceBus;

trait NamedMessageBusTrait
{
    /**
     * @var string
     */
    private $busName;

    /**
     * @var string
     */
    private $busType;

    /**
     * @var string[]
     */
    private $plugins = [];

    public function setBusName(string $busName): void
    {
        $this->busName = $busName;
    }

    public function busName(): string
    {
        return $this->busName;
    }

    public function setBusType(string $busType): void
    {
        $this->busType = $busType;
    }

    public function busType(): string
    {
        return $this->busType;
    }

    public function plugins(): array
    {
        return $this->plugins;
    }

    public function addPlugin(\Prooph\ServiceBus\Plugin\Plugin $plugin, string $serviceId = null): void
    {
        $plugin->attachToMessageBus($this);
        $this->plugins[] = ['plugin' => $plugin, 'service_id' => $serviceId];
    }
}
