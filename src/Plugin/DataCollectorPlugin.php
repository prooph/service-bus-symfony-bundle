<?php

declare(strict_types=1);

namespace Prooph\Bundle\ServiceBus\Plugin;

use Prooph\Bundle\ServiceBus\NamedMessageBus;
use Prooph\Common\Event\ActionEvent;
use Prooph\ServiceBus\Exception\RuntimeException;
use Prooph\ServiceBus\MessageBus;
use Prooph\ServiceBus\Plugin\Plugin;
use Prooph\ServiceBus\QueryBus;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\Stopwatch\Stopwatch;

class DataCollectorPlugin extends DataCollector implements Plugin
{
    /**
     * @var array
     */
    protected $listenerHandlers = [];

    /**
     * @var array
     */
    private $buses = [];

    /**
     * @var Stopwatch
     */
    private $stopwatch;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container, string $busType)
    {
        $this->stopwatch = new Stopwatch();
        $this->container = $container;
        $this->data['bus_type'] = $busType;
        $this->data['messages'] = [];
        $this->data['duration'] = [];
    }

    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        foreach ($this->buses as $bus) {
            $busName = $bus->busName();

            /*$reflClass = new ReflectionClass($bus);
            $reflProperty = $reflClass->getProperty('events');
            $reflProperty->setAccessible(true);
            todo maybe put as default value in config tree builder to also make it configurable?
            $this->data['config'][$busName]['action_event_emitter'] = get_class($reflProperty->getValue($bus)); */
            $this->data['config'][$busName] = $this->container->getParameter(
                sprintf('prooph_service_bus.%s.configuration', $busName)
            );
        }
    }

    public function getName(): string
    {
        return sprintf('prooph.%s_bus', $this->data['bus_type']);
    }

    public function totalMessageCount(): int
    {
        return array_sum(array_map(function ($v) {
            return count($v);
        }, $this->data['messages']));
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

    public function attachToMessageBus(MessageBus $messageBus): void
    {
        if ($messageBus instanceof QueryBus) {
            return;
        }

        $this->buses[] = $messageBus;
        if (! $messageBus instanceof NamedMessageBus) {
            throw new RuntimeException(sprinf(
                'To use the Symfony Datacollector, the Bus "%s" needs to implement "%s"',
                $messageBus,
                NamedMessageBus::class
            ));
        }

        $this->listenerHandlers[] = $messageBus->attach(MessageBus::EVENT_DISPATCH, function (ActionEvent $actionEvent) {
            $busName = $actionEvent->getTarget()->busName();
            $uuid = (string) $actionEvent->getParam(MessageBus::EVENT_PARAM_MESSAGE)->uuid();

            if (! $this->stopwatch->isStarted($busName)) {
                $this->stopwatch->start($busName);
            }

            $this->stopwatch->start($uuid);
        }, MessageBus::PRIORITY_INVOKE_HANDLER + 100);

        $this->listenerHandlers[] = $messageBus->attach(MessageBus::EVENT_FINALIZE, function (ActionEvent $actionEvent) {
            $busName = $actionEvent->getTarget()->busName();
            $uuid = (string) $actionEvent->getParam(MessageBus::EVENT_PARAM_MESSAGE)->uuid();

            $this->data['duration'][$busName] = $this->stopwatch->lap($busName)->getDuration();
            $this->data['messages'][$busName][$uuid] = $this->createContextFromActionEvent($actionEvent);
            $this->data['messages'][$busName][$uuid]['duration'] = $this->stopwatch->stop($uuid)->getDuration();
        }, MessageBus::PRIORITY_INVOKE_HANDLER - 100);

        $this->listenerHandlers[] = $messageBus->attach(MessageBus::EVENT_DISPATCH, function (ActionEvent $actionEvent) {
            foreach ($actionEvent->getParam('event-listeners', []) as $handler) {
                $this->data['message_callstack'][$actionEvent->getTarget()->busName()][] = [
                    'id' => $actionEvent->getParam('message')->uuid(),
                    'message' => $actionEvent->getParam('message-name'),
                    'handler' => \is_object($handler) ? get_class($handler) : $handler,
                ];
            }
            if ($actionEvent->getParam('message-handler') !== null) {
                $this->data['message_callstack'][$actionEvent->getTarget()->busName()][] = [
                    'id' => $actionEvent->getParam('message')->uuid(),
                    'message' => $actionEvent->getParam('message-name'),
                    'handler' => is_object($actionEvent->getParam('message-handler'))
                        ? get_class($actionEvent->getParam('message-handler'))
                        : (string) $actionEvent->getParam('message-handler'),
                ];
            }
        }, MessageBus::PRIORITY_ROUTE - 50000);
    }

    public function detachFromMessageBus(MessageBus $messageBus): void
    {
        foreach ($this->listenerHandlers as $listenerHandler) {
            $messageBus->detach($listenerHandler);
        }

        $this->listenerHandlers = [];
    }

    protected function createContextFromActionEvent(ActionEvent $event): array
    {
        return [
            'bus-name' => $event->getTarget()->busName(),
            'message-data' => $event->getParam('message')->toArray(),
            'message-name' => $event->getParam('message-name'),
            'message-handled' => $event->getParam('message-handled'),
            'message-handler' => is_object($event->getParam('message-handler'))
                ? get_class($event->getParam('message-handler'))
                : $event->getParam('message-handler'),
        ];
    }

    public function busType(): string
    {
        return $this->data['bus_type'];
    }
}
