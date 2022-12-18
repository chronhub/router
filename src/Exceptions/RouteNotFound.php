<?php

declare(strict_types=1);

namespace Chronhub\Message\Router\Exceptions;

use RuntimeException;

final class RouteNotFound extends RuntimeException
{
    public static function withMessageName(string $messageName): self
    {
        return new self("Message name $messageName not found");
    }
}
