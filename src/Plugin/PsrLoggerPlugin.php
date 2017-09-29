<?php

declare(strict_types=1);

namespace Prooph\Bundle\ServiceBus\Plugin;

use Prooph\Bundle\ServiceBus\NamedMessageBus;
use Prooph\Common\Event\ActionEvent;
use Prooph\ServiceBus\MessageBus;
use Prooph\ServiceBus\Plugin\AbstractPlugin;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ReflectionClass;

class PsrLoggerPlugin extends AbstractPlugin
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    public function attachToMessageBus(MessageBus $messageBus): void
    {
        $this->listenerHandlers[] = $messageBus->attach(MessageBus::EVENT_DISPATCH, function (ActionEvent $event) {
            $context = $this->createContextFromActionEvent($event);
            $message = 'Dispatched {bus-type}:{message-name}';
            if ($context['message-handler'] !== null) {
                $message = 'Dispatching {bus-type} {message-name} to handler {message-handler}';
            }
            $this->logger->info($message, $context);
        }, MessageBus::PRIORITY_INVOKE_HANDLER + 2000);

        $this->listenerHandlers[] = $messageBus->attach(MessageBus::EVENT_FINALIZE, function (ActionEvent $event) {
            $context = $this->createContextFromActionEvent($event);
            $message = 'Finished {bus-type}:{message-name}';
            if ($context['message-handler'] !== null) {
                $message = 'Finished {bus-type}: "{message-name}" by handler {message-handler}';
            }
            $this->logger->info($message, $context);
        }, -2000);

        $this->listenerHandlers[] = $messageBus->attach(MessageBus::EVENT_DISPATCH, function (ActionEvent $event) {
            $context = $this->createContextFromActionEvent($event);
            $this->logger->debug('Initialized {bus-type} message {message-name}', $context);
        }, MessageBus::PRIORITY_INITIALIZE - 100);

        $this->listenerHandlers[] = $messageBus->attach(MessageBus::EVENT_DISPATCH, function (ActionEvent $event) {
            $context = $this->createContextFromActionEvent($event);

            $this->logger->debug('Detect {bus-type} message name for {message-name}', $context);
        }, MessageBus::PRIORITY_DETECT_MESSAGE_NAME - 100);

        //Should be triggered because we did not provide a message-handler yet
        $this->listenerHandlers[] = $messageBus->attach(MessageBus::EVENT_DISPATCH, function (ActionEvent $event) {
            $context = $this->createContextFromActionEvent($event);

            $this->logger->debug(
                'Detect {bus-type} message route for {message-name}', $context);
        }, MessageBus::PRIORITY_ROUTE - 100);

        $this->listenerHandlers[] = $messageBus->attach(MessageBus::EVENT_DISPATCH, function (ActionEvent $event) {
            $context = $this->createContextFromActionEvent($event);

            $this->logger->debug(
                'Locate {bus-type} handler for {message-name}', $context);
        }, MessageBus::PRIORITY_LOCATE_HANDLER - 100);
    }

    protected function createContextFromActionEvent(ActionEvent $event): array
    {
        $context = [];
        if ($event->getTarget() instanceof NamedMessageBus) {
            $context['bus-type'] = $event->getTarget()->busType();
            $context['bus-name'] = $event->getTarget()->busName();
        } else {
            $reflect = new ReflectionClass($event->getTarget());
            $context['bus-type'] = $reflect->getShortName();
            $context['bus-name'] = 'anonymous';
        }

        return
            array_merge($context,
                [
                    'message-data' => $event->getParam('message')->toArray(),
                    'message-name' => $event->getParam('message-name'),
                    'message-handled' => $event->getParam('message-handled'),
                    'message-handler' => \is_object($event->getParam('message-handler')) ? get_class($event->getParam('message-handler')) : $event->getParam('message-handler'),
                ]
            );
    }
}
