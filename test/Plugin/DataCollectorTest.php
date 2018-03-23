<?php

declare(strict_types=1);

namespace ProophTest\Bundle\ServiceBus\Plugin;

use PHPUnit\Framework\TestCase;
use Prooph\Bundle\ServiceBus\Plugin\DataCollector;
use ProophTest\Bundle\ServiceBus\DependencyInjection\Fixture\Model\AcmeRegisterUserCommand;
use ProophTest\Bundle\ServiceBus\DependencyInjection\Fixture\Model\AcmeRegisterUserHandler;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/** @covers \Prooph\Bundle\ServiceBus\Plugin\DataCollector */
class DataCollectorTest extends TestCase
{
    /** @test */
    public function it_provides_its_bus_type(): void
    {
        $collector = new DataCollector(new Container(), 'command');
        $this->assertSame('command', $collector->busType());
    }

    /** @test */
    public function its_name_relates_to_the_bus_type_its_collecting_for(): void
    {
        $collector = new DataCollector(new Container(), 'command');
        $this->assertSame('prooph.command_bus', $collector->getName());
    }

    /** @test */
    public function it_collects_the_configuration_of_all_added_event_buses(): void
    {
        $config = ['config' => 'with values'];
        $container = new Container();
        $container->setParameter('prooph_service_bus.default_command_bus.configuration', $config);
        $collector = new DataCollector($container, 'command');

        $collector->addMessageBus('default_command_bus');

        $collector->collect(new Request(), new Response());

        $this->assertSame($config, $collector->config('default_command_bus'));
    }

    /** @test */
    public function it_counts_the_buses_for_which_at_least_one_message_duration_has_been_recorded(): void
    {
        $collector = new DataCollector(new Container(), 'command');

        $collector->addMessage('default_command_bus', 5, 'message-5', ['foo' => 'bar']);
        $collector->addMessage('another_command_bus', 8, 'message-8', ['baz' => 'bar']);
        $collector->addMessage('another_command_bus', 8, 'message-9', ['foo' => 'baz']);

        $this->assertSame(2, $collector->totalBusCount());
    }

    /** @test */
    public function it_counts_the_messages_that_has_been_recorded_for_all_buses(): void
    {
        $collector = new DataCollector(new Container(), 'command');

        $collector->addMessage('default_command_bus', 5, 'message-5', ['foo' => 'bar']);
        $collector->addMessage('another_command_bus', 8, 'message-8', ['baz' => 'bar']);
        $collector->addMessage('another_command_bus', 8, 'message-9', ['foo' => 'baz']);

        $this->assertSame(3, $collector->totalMessageCount());
    }

    /** @test */
    public function it_computes_the_total_message_handling_duration_of_all_buses(): void
    {
        $collector = new DataCollector(new Container(), 'command');

        $collector->addMessage('default_command_bus', 5, 'message-5', ['foo' => 'bar']);
        $collector->addMessage('another_command_bus', 8, 'message-8', ['baz' => 'bar']);

        $this->assertSame(13, $collector->totalBusDuration());
    }

    /** @test */
    public function it_collects_the_message_handling_duration_for_a_single_bus(): void
    {
        $collector = new DataCollector(new Container(), 'command');

        $collector->addMessage('default_command_bus', 5, 'message-5', ['foo' => 'bar']);
        $collector->addMessage('default_command_bus', 8, 'message-8', ['baz' => 'bar']);

        $this->assertSame(8, $collector->busDuration('default_command_bus'));
    }

    /** @test */
    public function it_provides_the_collected_messages_grouped_by_their_bus(): void
    {
        $collector = new DataCollector(new Container(), 'command');

        $collector->addMessage('default_command_bus', 5, 'message-5', ['foo' => 'bar']);
        $collector->addMessage('another_command_bus', 8, 'message-8', ['baz' => 'bar']);
        $collector->addMessage('another_command_bus', 8, 'message-9', ['foo' => 'baz']);

        $this->assertSame([
            'default_command_bus' => [
                'message-5' => ['foo' => 'bar'],
            ],
            'another_command_bus' => [
                'message-8' => ['baz' => 'bar'],
                'message-9' => ['foo' => 'baz'],
            ],
        ], $collector->messages());
    }

    /** @test */
    public function it_collects_callstacks_of_buses(): void
    {
        $log = [
            'id' => 'message-5',
            'message' => AcmeRegisterUserCommand::class,
            'handler' => AcmeRegisterUserHandler::class,
        ];

        $collector = new DataCollector(new Container(), 'command');

        $collector->addCallstack('default_command_bus', $log);

        $this->assertSame([$log], $collector->callstack('default_command_bus'));
    }

    /** @test */
    public function it_provides_empty_callstacks_for_each_bus_that_has_no_callstack_added(): void
    {
        $collector = new DataCollector(new Container(), 'command');

        $this->assertSame([], $collector->callstack('default_command_bus'));
    }

    /** @test */
    public function it_can_be_reset(): void
    {
        $log = [
            'id' => 'message-5',
            'message' => AcmeRegisterUserCommand::class,
            'handler' => AcmeRegisterUserHandler::class,
        ];
        $collector = new DataCollector(new Container(), 'command');
        $collector->addCallstack('default_command_bus', $log);
        $collector->addMessage('default_command_bus', 8, 'message-9', ['foo' => 'baz']);

        $collector->reset();

        $this->assertSame(0, $collector->totalMessageCount());
        $this->assertCount(0, $collector->callstack('default_command_bus'));
    }
}
