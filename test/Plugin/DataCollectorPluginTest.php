<?php

declare(strict_types=1);

namespace ProophTest\Bundle\ServiceBus\Plugin;

use PHPUnit\Framework\TestCase;
use Prooph\Bundle\ServiceBus\CommandBus;
use Prooph\Bundle\ServiceBus\Exception\RuntimeException;
use Prooph\Bundle\ServiceBus\MessageContext\ContextFactory;
use Prooph\Bundle\ServiceBus\Plugin\DataCollector;
use Prooph\Bundle\ServiceBus\Plugin\DataCollectorPlugin;
use Prooph\Common\Event\ActionEvent;
use Prooph\ServiceBus\CommandBus as NotNamedCommandBus;
use Prooph\ServiceBus\Exception\MessageDispatchException;
use Prooph\ServiceBus\MessageBus;
use Prooph\ServiceBus\QueryBus;
use ProophTest\Bundle\ServiceBus\DependencyInjection\Fixture\Model\AcmeRegisterUserCommand;

/** @covers \Prooph\Bundle\ServiceBus\Plugin\DataCollectorPlugin */
class DataCollectorPluginTest extends TestCase
{
    /** @test */
    public function it_cannot_be_attached_to_a_QueryBus(): void
    {
        $plugin = new DataCollectorPlugin(
            $this->createMock(ContextFactory::class),
            $this->createMock(DataCollector::class)
        );
        $queryBus = $this->createMock(QueryBus::class);

        $queryBus->expects($this->never())->method('attach');

        $plugin->attachToMessageBus($queryBus);
    }

    /** @test */
    public function it_cannot_be_attached_to_a_MessageBus_that_is_not_named(): void
    {
        $plugin = new DataCollectorPlugin(
            $this->createMock(ContextFactory::class),
            $this->createMock(DataCollector::class)
        );
        $messageBus = $this->createMock(NotNamedCommandBus::class);

        $this->expectException(RuntimeException::class);

        $plugin->attachToMessageBus($messageBus);
    }

    /** @test */
    public function it_will_add_the_MessageBus_to_the_DataCollector(): void
    {
        $dataCollector = $this->createMock(DataCollector::class);
        $plugin = new DataCollectorPlugin($this->createMock(ContextFactory::class), $dataCollector);
        $messageBus = $this->createNamedCommandBus('default_command_bus');

        $dataCollector->expects($this->once())->method('addMessageBus')->with('default_command_bus');

        $plugin->attachToMessageBus($messageBus);
    }

    /** @test */
    public function it_ignores_and_wont_fail_on_messages_that_do_not_implement_the_MessageInterface(): void
    {
        $dataCollector = $this->createMock(DataCollector::class);
        $plugin = new DataCollectorPlugin($this->createMock(ContextFactory::class), $dataCollector);
        $messageBus = $this->createNamedCommandBusWithDummyHandler();
        $plugin->attachToMessageBus($messageBus);

        $dataCollector->expects($this->never())->method('addMessage');
        $dataCollector->expects($this->never())->method('addCallstack');

        $messageBus->dispatch('message as string');
    }

    /** @test */
    public function it_logs_the_duration_of_a_Message_execution(): void
    {
        $dataCollector = $this->createMock(DataCollector::class);
        $plugin = new DataCollectorPlugin($this->createMock(ContextFactory::class), $dataCollector);
        $messageBus = $this->createNamedCommandBusWithDummyHandler();
        $plugin->attachToMessageBus($messageBus);

        $dataCollector->expects($this->once())->method('addMessage');

        $messageBus->dispatch(new AcmeRegisterUserCommand([]));
    }

    /** @test */
    public function it_logs_the_callstack_of_a_Message_execution(): void
    {
        $dataCollector = $this->createMock(DataCollector::class);
        $plugin = new DataCollectorPlugin($this->createMock(ContextFactory::class), $dataCollector);
        $messageBus = $this->createNamedCommandBusWithDummyHandler();
        $plugin->attachToMessageBus($messageBus);

        $dataCollector->expects($this->once())->method('addCallstack');

        $messageBus->dispatch(new AcmeRegisterUserCommand([]));
    }

    /** @test */
    public function it_does_not_log_the_callstack_if_not_handler_is_available(): void
    {
        $dataCollector = $this->createMock(DataCollector::class);
        $plugin = new DataCollectorPlugin($this->createMock(ContextFactory::class), $dataCollector);
        $messageBus = $this->createNamedCommandBus();
        $plugin->attachToMessageBus($messageBus);

        $dataCollector->expects($this->never())->method('addCallstack');

        try {
            $messageBus->dispatch(new AcmeRegisterUserCommand([]));
        } catch (MessageDispatchException $exception) {
            // no handler available
        }
    }

    private function createNamedCommandBus($name = 'default_command_bus'): CommandBus
    {
        $commandBus = new CommandBus();
        $commandBus->setBusName($name);

        return $commandBus;
    }

    private function createNamedCommandBusWithDummyHandler(): CommandBus
    {
        $commandBus = $this->createNamedCommandBus();
        $commandBus->attach(MessageBus::EVENT_DISPATCH, function (ActionEvent $event) {
            $event->setParam(MessageBus::EVENT_PARAM_MESSAGE_HANDLER, function () {
            });
        }, MessageBus::PRIORITY_ROUTE);

        return $commandBus;
    }
}
