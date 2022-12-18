<?php

declare(strict_types=1);

namespace Chronhub\Message\Router;

use Chronhub\Message\Router\Exceptions\RouterRuleViolation;
use function is_array;
use function array_merge;
use function class_exists;

final class Route
{
    /**
     * Message consumers
     *
     * @var array<null|string|object>
     */
    private array $consumers = [];

    /**
     * Route queue options
     *
     * @var QueueFactory|null
     */
    private ?QueueFactory $onQueue = null;

    public function __construct(private readonly string $name)
    {
        if (! class_exists($this->name)) {
            throw new RouterRuleViolation("Message name must be a valid class name, got $name");
        }
    }

    /**
     * Add consumers to current route
     *
     * @param  string|object  ...$consumers
     * @return $this
     */
    public function to(string|object ...$consumers): self
    {
        $this->consumers = array_merge($this->consumers, $consumers);

        return $this;
    }

    /**
     * Get route message handlers
     *
     * @return array
     */
    public function consumers(): array
    {
        return $this->consumers;
    }

    /**
     * Get route message name
     *
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Specify route queue options
     *
     * an empty array will return the default queue factory
     *
     * @param  QueueFactory|array  $queueFactory
     * @return $this
     */
    public function onQueue(QueueFactory|array $queueFactory): self
    {
        if (is_array($queueFactory)) {
            $queueFactory = new QueueFactory(...$queueFactory);
        }

        $this->onQueue = $queueFactory;

        return $this;
    }

    public function queue(): ?QueueFactory
    {
        return $this->onQueue;
    }
}
