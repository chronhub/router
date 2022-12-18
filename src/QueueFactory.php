<?php

declare(strict_types=1);

namespace Chronhub\Message\Router;

use Illuminate\Contracts\Support\Arrayable;

class QueueFactory implements Arrayable
{
    /**
     * Override specific arguments by passing a destructuring array
     *
     * eg: new QueueFactory(...['max_exceptions' => 3, 'timeout' => 30])
     * eg: new QueueFactory(name: 'default', tries: 2, maxExceptions: 4])
     */
    public function __construct(
        public readonly ?string $connection = null,
        public readonly ?string $name = null,
        public readonly ?int $tries = null,
        public readonly ?int $maxExceptions = null,
        public readonly null|int|string $delay = null,
        public readonly null|int $timeout = null)
    {
    }

    public function toArray(): array
    {
        return [
            'connection' => $this->connection,
            'name' => $this->name,
            'tries' => $this->tries,
            'max_exceptions' => $this->maxExceptions,
            'delay' => $this->delay,
            'timeout' => $this->timeout,
        ];
    }
}
