<?php

declare(strict_types=1);

namespace ProophTest\Bundle\ServiceBus\MessageContext;

use Exception;
use PHPUnit\Framework\TestCase;
use Prooph\Bundle\ServiceBus\MessageContext\DefaultMessageDataConverter;
use Prooph\Common\Messaging\Message;
use Prooph\Common\Messaging\MessageConverter;

/** @covers \Prooph\Bundle\ServiceBus\MessageContext\DefaultMessageDataConverter */
class DefaultMessageDataConverterTest extends TestCase
{
    /** @test */
    public function it_uses_as_MessageConverter_to_convert_Messages(): void
    {
        $message = $this->createMock(Message::class);
        $messageConverter = $this->createMock(MessageConverter::class);
        $messageConverter->expects($this->any())->method('convertToArray')->willReturn([
            'message-name' => 'create-todo',
        ]);

        $result = (new DefaultMessageDataConverter($messageConverter))->convertMessageToArray($message);

        $this->assertSame(['message-name' => 'create-todo'], $result);
    }

    /** @test */
    public function it_converts_the_message_to_an_empty_array_if_the_MessageConverter_throws_an_exception(): void
    {
        $message = $this->createMock(Message::class);
        $messageConverter = $this->createMock(MessageConverter::class);
        $messageConverter->expects($this->any())->method('convertToArray')->willThrowException(new Exception());

        $result = (new DefaultMessageDataConverter($messageConverter))->convertMessageToArray($message);

        $this->assertSame([], $result);
    }

    /** @test */
    public function it_simply_returns_the_message_if_the_message_is_an_array(): void
    {
        $message = ['name' => 'create-todo', 'text' => 'buy milk'];
        $messageConverter = $this->createMock(MessageConverter::class);
        $messageConverter->expects($this->never())->method('convertToArray');

        $result = (new DefaultMessageDataConverter($messageConverter))->convertMessageToArray($message);

        $this->assertSame($message, $result);
    }

    /** @test */
    public function it_converts_scalars_to_an_empty_array(): void
    {
        $messageConverter = $this->createMock(MessageConverter::class);
        $messageConverter->expects($this->never())->method('convertToArray');

        $result = (new DefaultMessageDataConverter($messageConverter))->convertMessageToArray('create-todo');

        $this->assertSame([], $result);
    }
}
