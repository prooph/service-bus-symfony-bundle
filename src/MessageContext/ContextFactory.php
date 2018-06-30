<?php

declare(strict_types=1);

namespace Prooph\Bundle\ServiceBus\MessageContext;

use Prooph\Bundle\ServiceBus\NamedMessageBus;
use Prooph\Common\Event\ActionEvent;
use Prooph\ServiceBus\EventBus;
use Prooph\ServiceBus\MessageBus;
use ReflectionClass;

/** @internal */
class ContextFactory
{
    /** @var MessageDataConverter */
    private $messageConverter;

    public function __construct(MessageDataConverter $messageDataConverter)
    {
        $this->messageConverter = $messageDataConverter;
    }

    public function createFromActionEvent(ActionEvent $event): array
    {
        $messageBus = $event->getTarget();
        $message = $event->getParam(MessageBus::EVENT_PARAM_MESSAGE);
        $handler = $event->getParam(MessageBus::EVENT_PARAM_MESSAGE_HANDLER);
        $listeners = $event->getParam(EventBus::EVENT_PARAM_EVENT_LISTENERS, []);

        $context = [
            'message-data' => $this->messageConverter->convertMessageToArray($message),
            'message-name' => $event->getParam(MessageBus::EVENT_PARAM_MESSAGE_NAME),
            'message-handled' => $event->getParam(MessageBus::EVENT_PARAM_MESSAGE_HANDLED),
            'message-handler' => $this->presentObjectOrScalar($handler),
            'event-listeners' => \array_map([$this, 'presentObjectOrScalar'], $listeners),
        ];

        if ($messageBus instanceof NamedMessageBus) {
            $context['bus-type'] = $messageBus->busType();
            $context['bus-name'] = $messageBus->busName();

            return $context;
        }

        $reflection = new ReflectionClass($messageBus);
        $context['bus-type'] = $reflection->getShortName();
        $context['bus-name'] = 'anonymous';

        return $context;
    }

    private function presentObjectOrScalar($objectOrScalar): ?string
    {
        if (null === $objectOrScalar) {
            return null;
        }

        return \is_object($objectOrScalar) ? \get_class($objectOrScalar) : (string) $objectOrScalar;
    }
}
