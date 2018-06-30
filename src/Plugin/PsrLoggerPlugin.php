<?php

declare(strict_types=1);

namespace Prooph\Bundle\ServiceBus\Plugin;

use Prooph\Bundle\ServiceBus\MessageContext\ContextFactory;
use Prooph\Common\Event\ActionEvent;
use Prooph\ServiceBus\MessageBus;
use Prooph\ServiceBus\Plugin\AbstractPlugin;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class PsrLoggerPlugin extends AbstractPlugin
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ContextFactory
     */
    private $contextFactory;

    public function __construct(ContextFactory $contextFactory, LoggerInterface $logger = null)
    {
        $this->contextFactory = $contextFactory;
        $this->logger = $logger ?? new NullLogger();
    }

    public function attachToMessageBus(MessageBus $messageBus): void
    {
        $this->listenerHandlers[] = $messageBus->attach(MessageBus::EVENT_DISPATCH, function (ActionEvent $event) {
            $context = $this->contextFactory->createFromActionEvent($event);
            $message = 'Dispatching {bus-type} "{message-name}"';
            if ($context['message-handler'] !== null) {
                $message .= ' to handler "{message-handler}"';
            } elseif (\count($context['event-listeners']) > 0) {
                $context['event-listeners'] = \implode(', ', $context['event-listeners']);
                $message .= ' to listeners "{event-listeners}"';
            }
            $this->logger->info($message, $context);
        }, MessageBus::PRIORITY_INVOKE_HANDLER + 2000);

        $this->listenerHandlers[] = $messageBus->attach(MessageBus::EVENT_FINALIZE, function (ActionEvent $event) {
            $context = $this->contextFactory->createFromActionEvent($event);
            $message = 'Finished dispatch of {bus-type} "{message-name}"';
            $this->logger->info($message, $context);
        }, -2000);

        $this->listenerHandlers[] = $messageBus->attach(MessageBus::EVENT_DISPATCH, function (ActionEvent $event) {
            $context = $this->contextFactory->createFromActionEvent($event);
            $this->logger->debug('Initialized {bus-type} message "{message-name}"', $context);
        }, MessageBus::PRIORITY_INITIALIZE - 100);

        $this->listenerHandlers[] = $messageBus->attach(MessageBus::EVENT_DISPATCH, function (ActionEvent $event) {
            $context = $this->contextFactory->createFromActionEvent($event);
            $this->logger->debug('Detected {bus-type} message name for "{message-name}"', $context);
        }, MessageBus::PRIORITY_DETECT_MESSAGE_NAME - 100);

        //Should be triggered because we did not provide a message-handler yet
        $this->listenerHandlers[] = $messageBus->attach(MessageBus::EVENT_DISPATCH, function (ActionEvent $event) {
            $context = $this->contextFactory->createFromActionEvent($event);
            $this->logger->debug('Detected {bus-type} message route for "{message-name}"', $context);
        }, MessageBus::PRIORITY_ROUTE - 100);

        $this->listenerHandlers[] = $messageBus->attach(MessageBus::EVENT_DISPATCH, function (ActionEvent $event) {
            $context = $this->contextFactory->createFromActionEvent($event);
            $this->logger->debug('Located {bus-type} handler for "{message-name}"', $context);
        }, MessageBus::PRIORITY_LOCATE_HANDLER - 100);
    }
}
