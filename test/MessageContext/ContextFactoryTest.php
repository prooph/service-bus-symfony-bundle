<?php

declare(strict_types=1);

namespace ProophTest\Bundle\ServiceBus\MessageContext;

use PHPUnit\Framework\TestCase;
use Prooph\Bundle\ServiceBus\MessageContext\ContextFactory;
use Prooph\Bundle\ServiceBus\MessageContext\MessageDataConverter;
use Prooph\Bundle\ServiceBus\NamedMessageBus;
use Prooph\Common\Event\DefaultActionEvent;
use Prooph\Common\Messaging\Message;
use Prooph\ServiceBus\MessageBus;
use stdClass;

/** @covers \Prooph\Bundle\ServiceBus\MessageContext\ContextFactory */
class ContextFactoryTest extends TestCase
{
    /** @test */
    public function it_creates_a_context_array_from_an_action_event(): void
    {
        $messageData = ['message_name' => 'my-acme-message'];

        $messageConverter = $this->createMock(MessageDataConverter::class);
        $messageBus = $this->createMock(NamedMessageBus::class);
        $messageBus->expects($this->any())->method('busType')->willReturn('command');
        $messageBus->expects($this->any())->method('busName')->willReturn('acme_command_bus');
        $messageConverter->expects($this->any())->method('convertMessageToArray')->willReturn($messageData);

        $actionEvent = new DefaultActionEvent('name-is-not-important', $messageBus, [
            MessageBus::EVENT_PARAM_MESSAGE => $this->createMock(Message::class),
            MessageBus::EVENT_PARAM_MESSAGE_NAME => 'my-acme-message',
            MessageBus::EVENT_PARAM_MESSAGE_HANDLED => true,
            MessageBus::EVENT_PARAM_MESSAGE_HANDLER => new stdClass(),
        ]);

        $result = (new ContextFactory($messageConverter))->createFromActionEvent($actionEvent);

        $this->assertSame($result, [
            'message-data' => $messageData,
            'message-name' => 'my-acme-message',
            'message-handled' => true,
            'message-handler' => 'stdClass',
            'bus-type' => 'command',
            'bus-name' => 'acme_command_bus',
        ]);
    }

    /** @test */
    public function it_uses_anonymous_as_name_for_not_NamedMessageBuses(): void
    {
        $messageData = ['message_name' => 'my-acme-message'];

        $messageConverter = $this->createMock(MessageDataConverter::class);
        $messageBus = $this->createMock(MessageBus::class);
        $messageConverter->expects($this->any())->method('convertMessageToArray')->willReturn($messageData);

        $actionEvent = new DefaultActionEvent('name-is-not-important', $messageBus, [
            MessageBus::EVENT_PARAM_MESSAGE => $this->createMock(Message::class),
            MessageBus::EVENT_PARAM_MESSAGE_NAME => 'my-acme-message',
            MessageBus::EVENT_PARAM_MESSAGE_HANDLED => true,
            MessageBus::EVENT_PARAM_MESSAGE_HANDLER => new stdClass(),
        ]);

        $result = (new ContextFactory($messageConverter))->createFromActionEvent($actionEvent);

        $this->assertSame($result['bus-name'], 'anonymous');
    }
}
