<?php

declare(strict_types=1);

namespace Chronhub\Message\Router;

use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Chronhub\Contracts\Message\Header;
use Chronhub\Contracts\Message\Envelop;
use Chronhub\Contracts\Router\RouteLocator;
use Illuminate\Contracts\Container\Container;
use Chronhub\Message\Router\Exceptions\RouteNotFound;
use Chronhub\Message\Router\Exceptions\RouteHandlerNotSupported;
use function is_string;
use function is_callable;
use function method_exists;

final class FindRoute implements RouteLocator
{
    public function __construct(private readonly Group $group,
                                private readonly Container $container)
    {
    }

    public function route(Envelop $message): Enumerable
    {
        $messageName = $message->header(Header::EVENT_TYPE);

        return $this
            ->determineMessageHandler($messageName)
            ->map(fn ($messageHandler): callable => $this->toCallable($messageHandler, $messageName));
    }

    public function onQueue(Envelop $message): ?array
    {
        $messageName = $message->header(Header::EVENT_TYPE);

        $route = $this->group->routes()->match($messageName);

        if (! $route instanceof Route) {
            throw RouteNotFound::withMessageName($messageName);
        }

        return $route->queue()?->toArray();
    }

    /**
     * Transform each message handler to callable if not already invokable
     *
     * @param  callable|object|string  $consumer
     * @param  string  $messageName
     * @return callable
     */
    private function toCallable(callable|object|string $consumer, string $messageName): callable
    {
        if (is_string($consumer)) {
            $consumer = $this->container[$consumer];
        }

        if (is_callable($consumer)) {
            return $consumer;
        }

        $callableMethod = $this->group->builder()->consumerMethodName();

        if (is_string($callableMethod) && method_exists($consumer, $callableMethod)) {
            return $consumer->$callableMethod(...);
        }

        throw RouteHandlerNotSupported::withMessageName($messageName);
    }

    /**
     * Find consumers by his message name
     *
     * @param  string  $messageName
     * @return Enumerable
     */
    private function determineMessageHandler(string $messageName): Enumerable
    {
        $route = $this->group->routes()->match($messageName);

        if ($route instanceof Route) {
            return Collection::wrap($route->consumers());
        }

        throw RouteNotFound::withMessageName($messageName);
    }
}
