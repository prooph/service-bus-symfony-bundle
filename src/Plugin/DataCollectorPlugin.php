<?php

declare(strict_types=1);

namespace Prooph\Bundle\ServiceBus\Plugin;

use Prooph\Bundle\ServiceBus\Exception\RuntimeException;
use Prooph\Bundle\ServiceBus\MessageContext\ContextFactory;
use Prooph\Bundle\ServiceBus\NamedMessageBus;
use Prooph\Common\Event\ActionEvent;
use Prooph\Common\Messaging\Message;
use Prooph\ServiceBus\MessageBus;
use Prooph\ServiceBus\Plugin\AbstractPlugin;
use Prooph\ServiceBus\QueryBus;
use Symfony\Component\Stopwatch\Stopwatch;

class DataCollectorPlugin extends AbstractPlugin
{
    /**
     * @var DataCollector
     */
    private $data;

    /**
     * @var Stopwatch
     */
    private $stopwatch;

    /**
     * @var ContextFactory
     */
    private $contextFactory;

    public function __construct(ContextFactory $contextFactory, DataCollector $data)
    {
        $this->stopwatch = new Stopwatch();
        $this->contextFactory = $contextFactory;
        $this->data = $data;
    }

    public function attachToMessageBus(MessageBus $messageBus): void
    {
        if ($messageBus instanceof QueryBus) {
            return;
        }

        if (! $messageBus instanceof NamedMessageBus) {
            throw new RuntimeException(sprintf(
                'To use the Symfony DataCollector, the Bus "%s" needs to implement "%s"',
                get_class($messageBus),
                NamedMessageBus::class
            ));
        }

        $this->data->addMessageBus($messageBus->busName());

        $this->listenerHandlers[] = $messageBus->attach(MessageBus::EVENT_DISPATCH, function (ActionEvent $actionEvent) {
            /** @var NamedMessageBus $target Is ensured above */
            $target = $actionEvent->getTarget();
            $busName = $target->busName();
            $message = $actionEvent->getParam(MessageBus::EVENT_PARAM_MESSAGE);
            if (! $message instanceof Message) {
                return;
            }
            $uuid = (string) $message->uuid();

            if (! $this->stopwatch->isStarted($busName)) {
                $this->stopwatch->start($busName);
            }

            $this->stopwatch->start($uuid);
        }, MessageBus::PRIORITY_INVOKE_HANDLER + 100);

        $this->listenerHandlers[] = $messageBus->attach(MessageBus::EVENT_FINALIZE, function (ActionEvent $actionEvent) {
            /** @var NamedMessageBus $messageBus Is ensured above */
            $messageBus = $actionEvent->getTarget();
            $busName = $messageBus->busName();
            $message = $actionEvent->getParam(MessageBus::EVENT_PARAM_MESSAGE);
            if (! $message instanceof Message) {
                return;
            }
            $uuid = (string) $message->uuid();
            $data = $this->contextFactory->createFromActionEvent($actionEvent);
            $data['duration'] = $this->stopwatch->stop($uuid)->getDuration();

            /** @var int $duration Is ensured by creating the stopwatch with less precision */
            $duration = $this->stopwatch->lap($busName)->getDuration();
            $this->data->addMessage($busName, $duration, $uuid, $data);
        }, MessageBus::PRIORITY_INVOKE_HANDLER - 100);

        $this->listenerHandlers[] = $messageBus->attach(MessageBus::EVENT_DISPATCH, function (ActionEvent $actionEvent) {
            /** @var NamedMessageBus $messageBus Is ensured above */
            $messageBus = $actionEvent->getTarget();
            $message = $actionEvent->getParam(MessageBus::EVENT_PARAM_MESSAGE);
            $messageName = $actionEvent->getParam(MessageBus::EVENT_PARAM_MESSAGE_NAME);
            $handler = $actionEvent->getParam(MessageBus::EVENT_PARAM_MESSAGE_HANDLER);
            if (! $message instanceof Message) {
                return;
            }
            $log = [
                'id' => (string) $message->uuid(),
                'message' => $messageName,
                'handler' => is_object($handler) ? get_class($handler) : (string) $handler,
            ];
            foreach ($actionEvent->getParam('event-listeners', []) as $handler) {
                $this->data->addCallstack($messageBus->busName(), $log);
            }
            if ($handler !== null) {
                $this->data->addCallstack($messageBus->busName(), $log);
            }
        }, MessageBus::PRIORITY_ROUTE - 50000);
    }
}
