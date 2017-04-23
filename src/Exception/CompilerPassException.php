<?php

declare(strict_types=1);

namespace Prooph\Bundle\ServiceBus\Exception;

class CompilerPassException extends \RuntimeException
{
    public static function messageTagMissing(string $serviceId): self
    {
        throw new self(
            sprintf('The "message" tag key is missing from tag. Either provide a "message" tag or enable "message_detection" for service "%s"',
                $serviceId
            )
        );
    }

    public static function tagCountExceeded(string $type, string $serviceId, string $busName): self
    {
        throw new self(
            sprintf(
                'More than 1 %s handler tagged on service "%s" with tag "%s". Only events can have multiple handlers',
                $type, $serviceId, $busName
            )
        );
    }
}
