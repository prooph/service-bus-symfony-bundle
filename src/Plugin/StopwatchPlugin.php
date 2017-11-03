<?php

declare(strict_types=1);

namespace Prooph\Bundle\ServiceBus\Plugin;

use Prooph\Bundle\ServiceBus\NamedMessageBus;
use Prooph\Common\Event\ActionEvent;
use Prooph\Common\Messaging\Message;
use Prooph\ServiceBus\MessageBus;
use Prooph\ServiceBus\Plugin\AbstractPlugin;
use Prooph\ServiceBus\QueryBus;
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
        if ($messageBus instanceof QueryBus) {
            return;
        }

        $resolveName = function (ActionEvent $event): string {
            /* @var $message Message ensured below */
            $message = $event->getParam(MessageBus::EVENT_PARAM_MESSAGE);
            $messageBus = $event->getTarget();
            if ($messageBus instanceof NamedMessageBus) {
                return sprintf(
                    '%s:%s:%s',
                    $message->messageType(),
                    $messageBus->busName(),
                    $event->getParam(MessageBus::EVENT_PARAM_MESSAGE_NAME)
                );
            }

            return sprintf(
                '%s:%s',
                $message->messageType(),
                $event->getParam(MessageBus::EVENT_PARAM_MESSAGE_NAME)
            );
        };

        $this->listenerHandlers[] = $messageBus->attach(MessageBus::EVENT_DISPATCH, function (ActionEvent $event) use ($resolveName) {
            $message = $event->getParam(MessageBus::EVENT_PARAM_MESSAGE);
            if (! $message instanceof Message) {
                return;
            }
            $messageType = $message->messageType();
            $this->stopwatch->start($resolveName($event), $messageType === 'command' ? 'section' : $messageType);
        }, MessageBus::PRIORITY_INVOKE_HANDLER + 2000);

        $this->listenerHandlers[] = $messageBus->attach(MessageBus::EVENT_FINALIZE, function (ActionEvent $event) use ($resolveName) {
            if (! $event->getParam(MessageBus::EVENT_PARAM_MESSAGE) instanceof Message) {
                return;
            }
            $this->stopwatch->stop($resolveName($event));
        }, -2000);
    }
}
