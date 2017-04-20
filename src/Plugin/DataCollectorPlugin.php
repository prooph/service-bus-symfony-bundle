<?php

declare(strict_types=1);

namespace Prooph\Bundle\ServiceBus\Plugin;

use Prooph\Bundle\ServiceBus\NamedMessageBus;
use Prooph\Common\Event\ActionEvent;
use Prooph\ServiceBus\Exception\RuntimeException;
use Prooph\ServiceBus\MessageBus;
use Prooph\ServiceBus\Plugin\Plugin;
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
    private $messageBusListener = [];

    /**
     * @var Stopwatch
     */
    private $stopwatch;

    public function __construct(ContainerInterface $container, string $busType)
    {
        $this->stopwatch = new Stopwatch();
        $this->data['bus_type'] = $busType;
        $this->container = $container;
        $this->data['messages'] = [];
        $this->data['duration'] = [];
        $this->buses = [];
    }

    /**
     * @inheritDoc
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {

        foreach ($this->buses as $bus) {
            $busName = $bus->busName();

            $reflClass = new \ReflectionClass($bus);
            $reflProperty = $reflClass->getProperty('events');
            $reflProperty->setAccessible(true);
            $this->data['config'][$busName]['action_event_emitter'] = get_class($reflProperty->getValue($bus));

            $this->data['plugins'][$busName] = array_map(function ($v) {
                $class = get_class($v['plugin']);
                unset($v['plugin']);

                return array_merge($v, ['class' => $class]);
            }, $bus->plugins());
        }
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return sprintf("prooph.%s_bus", $this->data['bus_type']);
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

    public function plugins(string $busName) : array
    {
        return $this->data['plugins'][$busName];
    }

    public function config(string $busName) : array
    {
        return $this->data['config'][$busName];
    }

    public function totalBusDuration(): int
    {
        return array_sum($this->data['duration']);
    }

    public function attachToMessageBus(MessageBus $messageBus): void
    {
        $this->buses[] = $messageBus;
        if (!$messageBus instanceof NamedMessageBus) {
            throw new RuntimeException(sprinf('To use the Symfony Datacollector, the Bus "%s" needs to implement "%s"', $messageBus, NamedMessageBus::class));
        }

        $this->messageBusListener = $messageBus->attach(MessageBus::EVENT_DISPATCH, function (ActionEvent $actionEvent) {
            $busName = $actionEvent->getTarget()->busName();
            $uuid = (string)$actionEvent->getParam(MessageBus::EVENT_PARAM_MESSAGE)->uuid();

            if (!$this->stopwatch->isStarted($busName)) {
                $this->stopwatch->start($busName);
            }

            $this->stopwatch->start($uuid);
            $this->data['messages'][$busName][$uuid] = $this->createContextFromActionEvent($actionEvent);
        });

        $this->messageBusListener = $messageBus->attach(MessageBus::EVENT_FINALIZE, function (ActionEvent $actionEvent) {
            $busName = $actionEvent->getTarget()->busName();
            $uuid = (string)$actionEvent->getParam(MessageBus::EVENT_PARAM_MESSAGE)->uuid();

            $this->data['duration'][$busName] = $this->stopwatch->stop($busName)->getDuration();
            $this->data['messages'][$busName][$uuid]['duration'] = $this->stopwatch->stop($uuid)->getDuration();
        });
    }

    public function detachFromMessageBus(MessageBus $messageBus): void
    {
        foreach ($this->messageBusListener as $listener) {
            $messageBus->detach($listener);
        }
    }

    protected function createContextFromActionEvent(ActionEvent $event): array
    {
        return [
            'bus-name' => $event->getTarget()->busName(),
            'message-data' => $event->getParam('message')->toArray(),
            'message-name' => $event->getParam('message-name'),
            'message-handled' => $event->getParam('message-handled'),
            'message-handler' => \is_object($event->getParam('message-handler')) ? get_class($event->getParam('message-handler')) : $event->getParam('message-handler'),
        ];
    }

    public function busType(): string
    {
        return $this->data['bus_type'];
    }
}
