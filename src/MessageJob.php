<?php

declare(strict_types=1);

namespace Chronhub\Message\Router;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\Queue;
use Chronhub\Contracts\Message\Header;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Container\Container;

final class MessageJob
{
    use InteractsWithQueue;
    use Queueable;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public int $tries = 1;

    /**
     * The maximum number of unhandled exceptions to allow before failing
     *
     * @var int
     */
    public int $maxExceptions = 3;

    /**
     * The number of seconds the job can run before timing out
     *
     * @var int
     */
    public int $timeout = 30;

    public function __construct(public readonly array $payload)
    {
        $this->setQueueOptions($this->payload['headers']['queue'] ?? []);
    }

    /**
     * Consume message
     */
    public function handle(Container $container): void
    {
        $container[$this->payload['headers'][Header::REPORTER_ID]]->relay($this->payload);
    }

    /**
     * Internally used by laravel
     */
    public function queue(Queue $queue, MessageJob $messageJob): void
    {
        $queue->pushOn($this->queue, $messageJob);
    }

    /**
     * Display message name
     *
     * @return string
     */
    public function displayName(): string
    {
        return $this->payload['headers'][Header::EVENT_TYPE];
    }

    private function setQueueOptions(array $queue): void
    {
        $this->connection = $queue['connection'] ?? $this->connection;
        $this->queue = $queue['name'] ?? $this->queue;
        $this->tries = $queue['tries'] ?? $this->tries;
        $this->delay = $queue['delay'] ?? $this->delay;
        $this->maxExceptions = $queue['max_exceptions'] ?? $this->maxExceptions;
        $this->timeout = $queue['timeout'] ?? $this->timeout;
    }
}
