<?php

declare(strict_types=1);

namespace ProophTest\Bundle\ServiceBus\Plugin;

use PHPUnit\Framework\TestCase;
use Prooph\Bundle\ServiceBus\CommandBus;
use Prooph\Bundle\ServiceBus\Plugin\StopwatchPlugin;
use Prooph\Bundle\ServiceBus\QueryBus;
use Prooph\Common\Event\ActionEvent;
use Prooph\Common\Event\ProophActionEventEmitter;
use Prooph\Common\Messaging\Command;
use Prooph\ServiceBus\MessageBus;
use Symfony\Component\Stopwatch\Stopwatch;

/** @covers \Prooph\Bundle\ServiceBus\Plugin\StopwatchPlugin */
class StopwatchPluginTest extends TestCase
{
    /** @test */
    public function it_times_the_the_runtime_of_events(): void
    {
        $commandBus = $this->createNamedNullCommandBus();

        $stopWatch = new Stopwatch();
        $plugin = new StopwatchPlugin($stopWatch);
        $plugin->attachToMessageBus($commandBus);

        $commandBus->dispatch($this->createCommand());

        $this->assertCount(1, $stopWatch->getSectionEvents('__root__'));
    }

    /** @test */
    public function it_cannot_be_attached_to_a_QueryBus(): void
    {
        $bus = $this->createQueryBus();

        $stopWatch = new Stopwatch();
        $plugin = new StopwatchPlugin($stopWatch);
        $plugin->attachToMessageBus($bus);

        $this->assertCount(0, $bus->plugins());
    }

    private function createCommand(): Command
    {
        return $this->createMock(Command::class);
    }

    private function createQueryBus(): QueryBus
    {
        return new QueryBus();
    }

    private function createNamedNullCommandBus(): CommandBus
    {
        $handler = function () {
        };
        $eventEmitter = new ProophActionEventEmitter();
        $eventEmitter->attachListener(MessageBus::EVENT_DISPATCH, function (ActionEvent $event) use ($handler) {
            $event->setParam(MessageBus::EVENT_PARAM_MESSAGE_HANDLER, $handler);
        }, MessageBus::PRIORITY_LOCATE_HANDLER + 10);

        $commandBus = new CommandBus($eventEmitter);
        $commandBus->setBusName('we must set a name');

        return $commandBus;
    }
}
