<?php

declare(strict_types=1);

namespace Prooph\Bundle\ServiceBus\MessageContext;

use Prooph\Bundle\ServiceBus\NamedMessageBus;
use Prooph\Common\Event\ActionEvent;
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

        $context = [
            'message-data' => $this->messageConverter->convertMessageToArray($message),
            'message-name' => $event->getParam(MessageBus::EVENT_PARAM_MESSAGE_NAME),
            'message-handled' => $event->getParam(MessageBus::EVENT_PARAM_MESSAGE_HANDLED),
            'message-handler' => is_object($handler) ? get_class($handler) : (string) $handler,
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
}
