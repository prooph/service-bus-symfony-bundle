<?php

declare(strict_types=1);

namespace Prooph\Bundle\ServiceBus\Plugin;

use Prooph\Bundle\ServiceBus\NamedMessageBus;
use Prooph\Common\Event\ActionEvent;
use Prooph\ServiceBus\MessageBus;
use Prooph\ServiceBus\Plugin\AbstractPlugin;
use Prooph\ServiceBus\Plugin\Plugin;
use Symfony\Component\Stopwatch\Stopwatch;

class StopwatchPlugin extends AbstractPlugin
{
    /**
     * @var Stopwatch
     */
    private $stopwatch;

    public function __construct(Stopwatch $stopwatch)
    {
        $this->stopwatch = $stopwatch;
    }

    public function attachToMessageBus(MessageBus $messageBus): void
    {
        $resolveName = function (ActionEvent $event) {
            if ($event->getTarget() instanceof NamedMessageBus) {
                return sprintf(
                    "%s:%s:%s",
                    $event->getParam(MessageBus::EVENT_PARAM_MESSAGE)->messageType(),
                    $event->getTarget()->busName(),
                    $event->getParam(MessageBus::EVENT_PARAM_MESSAGE_NAME)
                );
            } else {
                return sprintf(
                    "%s:%s",
                    $event->getParam(MessageBus::EVENT_PARAM_MESSAGE)->messageType(),
                    $event->getParam(MessageBus::EVENT_PARAM_MESSAGE_NAME)
                );
            }
        };

        $this->listenerHandlers[] = $messageBus->attach(MessageBus::EVENT_DISPATCH, function (ActionEvent $event) use ($resolveName) {
            $messageType = $event->getParam(MessageBus::EVENT_PARAM_MESSAGE)->messageType();
            $this->stopwatch->start($resolveName($event), $messageType === 'command' ? 'section' : $messageType);
        }, MessageBus::PRIORITY_INVOKE_HANDLER + 2000);

        $this->listenerHandlers[] = $messageBus->attach(MessageBus::EVENT_FINALIZE, function (ActionEvent $event) use ($resolveName) {
            $this->stopwatch->stop($resolveName($event));
        }, -2000);
    }
}
