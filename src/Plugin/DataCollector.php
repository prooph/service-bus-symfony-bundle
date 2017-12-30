<?php

declare(strict_types=1);

namespace Prooph\Bundle\ServiceBus\Plugin;

use Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector as BaseDataCollector;

class DataCollector extends BaseDataCollector
{
    /** @var string[] */
    private $busNames = [];

    /** @var ContainerInterface */
    private $container;

    public function __construct(ContainerInterface $container, string $busType)
    {
        $this->container = $container;
        $this->data['bus_type'] = $busType;
        $this->data['messages'] = [];
        $this->data['duration'] = [];
        $this->data['message_callstack'] = [];
    }

    public function collect(Request $request, Response $response, Exception $exception = null): void
    {
        foreach ($this->busNames as $busName) {
            $this->data['config'][$busName] = $this->container->getParameter(
                sprintf('prooph_service_bus.%s.configuration', $busName)
            );
        }
    }

    public function reset(): void
    {
        $this->data['messages'] = [];
        $this->data['duration'] = [];
        $this->data['message_callstack'] = [];
        $this->busNames = [];
    }

    public function totalMessageCount(): int
    {
        return array_sum(array_map('count', $this->data['messages']));
    }

    public function totalBusCount(): int
    {
        return count($this->data['messages']);
    }

    public function messages(): array
    {
        return $this->data['messages'];
    }

    public function busDuration(string $busName): int
    {
        return $this->data['duration'][$busName];
    }

    public function callstack(string $busName): array
    {
        return $this->data['message_callstack'][$busName] ?? [];
    }

    public function config(string $busName): array
    {
        return $this->data['config'][$busName];
    }

    public function totalBusDuration(): int
    {
        return array_sum($this->data['duration']);
    }

    public function busType(): string
    {
        return $this->data['bus_type'];
    }

    public function getName(): string
    {
        return sprintf('prooph.%s_bus', $this->data['bus_type']);
    }

    public function addMessageBus(string $busName): void
    {
        $this->busNames[] = $busName;
    }

    public function addMessage(string $busName, int $totalBusDuration, string $messageId, array $data): void
    {
        $this->data['duration'][$busName] = $totalBusDuration;
        $this->data['messages'][$busName][$messageId] = $data;
    }

    public function addCallstack(string $busName, array $log): void
    {
        $this->data['message_callstack'][$busName][] = $log;
    }
}
