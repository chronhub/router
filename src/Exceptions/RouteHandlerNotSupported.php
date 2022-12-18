<?php

declare(strict_types=1);

namespace Chronhub\Message\Router\Exceptions;

use RuntimeException;

final class RouteHandlerNotSupported extends RuntimeException
{
    public static function withMessageName(string $messageName): self
    {
        return new self("Message handler with name $messageName not supported");
    }
}
