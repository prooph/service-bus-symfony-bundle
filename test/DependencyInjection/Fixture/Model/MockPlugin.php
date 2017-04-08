<?php
/**
 * prooph (http://getprooph.org/)
 *
 * @see       https://github.com/prooph/service-bus-symfony-bundle for the canonical source repository
 * @copyright Copyright (c) 2016 prooph software GmbH (http://prooph-software.com/)
 * @license   https://github.com/prooph/service-bus-symfony-bundle/blob/master/LICENSE.md New BSD License
 */
declare(strict_types=1);

namespace ProophTest\Bundle\ServiceBus\DependencyInjection\Fixture\Model;

use Prooph\Common\Event\ActionEvent;
use Prooph\Common\Event\ActionEventEmitter;
use Prooph\Common\Event\DetachAggregateHandlers;
use Prooph\ServiceBus\MessageBus;
use Prooph\ServiceBus\Plugin\AbstractPlugin;

class MockPlugin extends AbstractPlugin
{
    use DetachAggregateHandlers;

    private $fired = false;

    public function wasFired(): bool
    {
        return $this->fired;
    }

    /**
     * @param ActionEventEmitter $dispatcher
     */
    public function attachToMessageBus(MessageBus $bus): void
    {
        $this->trackHandler($bus->attach(
            MessageBus::EVENT_DISPATCH,
            [$this, 'onInitialize'],
            MessageBus::PRIORITY_INITIALIZE
        ));
    }

    public function onInitialize(ActionEvent $event)
    {
        $this->fired = true;
    }

    public function reset()
    {
        $this->fired = false;
    }
}
