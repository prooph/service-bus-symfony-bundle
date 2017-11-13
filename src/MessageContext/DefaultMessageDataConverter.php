<?php

declare(strict_types=1);

namespace Prooph\Bundle\ServiceBus\MessageContext;

use Prooph\Common\Messaging\Message;
use Prooph\Common\Messaging\MessageConverter;
use Throwable;

/** @internal */
final class DefaultMessageDataConverter implements MessageDataConverter
{
    /** @var MessageConverter */
    private $messageConverter;

    public function __construct(MessageConverter $messageConverter)
    {
        $this->messageConverter = $messageConverter;
    }

    public function convertMessageToArray($message): array
    {
        if ($message instanceof Message) {
            try {
                return $this->messageConverter->convertToArray($message);
            } catch (Throwable $exception) {
                return [];
            }
        }

        if (is_array($message)) {
            return $message;
        }

        return [];
    }
}
