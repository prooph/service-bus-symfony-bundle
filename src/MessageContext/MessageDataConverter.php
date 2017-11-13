<?php

declare(strict_types=1);

namespace Prooph\Bundle\ServiceBus\MessageContext;

use Prooph\Common\Messaging\Message;
use Prooph\Common\Messaging\MessageConverter;

interface MessageDataConverter
{
    /**
     * The MessageDataConverter converts a message into an array.
     *
     * It is less strict on parameter and result as the MessageConverter:
     * The parameter MIGHT be an instance of \Prooph\Common\Messaging\Message.
     * The result array SHOULD contain the following data structure:
     *
     * [
     *   'message_name' => string,
     *   'uuid' => string,
     *   'payload' => array, //MUST only contain sub arrays and/or scalar types, objects, etc. are not allowed!
     *   'metadata' => array, //MUST only contain key/value pairs with values being only scalar types!
     *   'created_at' => \DateTimeInterface,
     * ]
     *
     * @see Message
     * @see MessageConverter
     */
    public function convertMessageToArray($message): array;
}
