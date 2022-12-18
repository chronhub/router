<?php

declare(strict_types=1);

namespace Chronhub\Message\Router;

use Illuminate\Support\Arr;
use Illuminate\Contracts\Container\Container;
use function count;
use function is_array;
use function is_object;
use function is_string;
use function array_is_list;

class GroupFactory
{
    public function __construct(private readonly Container $container)
    {
    }

    /**
     * @param  Group  $group
     * @param  array  $config
     * @return void
     */
    public function make(Group $group, array $config): void
    {
        $this->configGroup($group->builder(), $config);

        $this->bindRoutes($group->routes(), $config['routes']);
    }

    /**
     * @param  Builder  $group
     * @param  array  $router
     * @return void
     */
    protected function configGroup(Builder $group, array $router): void
    {
        if (isset($router['concrete'])) {
            $group->withReporterConcreteClass($router['concrete']);
        }

        if (isset($router['service_id'])) {
            $group->withReporterServiceId($router['service_id']);
        }

        if (isset($router['producer_service'])) {
            $group->withProducerServiceId($router['producer_service']);
        }

        if (isset($router['strategy'])) {
            $group->withProducerStrategy($router['strategy']);
        }

        if (isset($router['queue'])) {
            $group->withQueueFactory($router['queue']);
        }

        if (isset($router['tracker_id'])) {
            $group->withTrackerId($router['tracker_id']);
        }

        if (isset($router['method_name'])) {
            $group->withConsumerMethodName($router['method_name']);
        }

        $group
            ->withMessageSubscribers(...$router['message_subscribers'] ?? [])
            ->withMessageDecorators(...$router['message_decorators'] ?? []);
    }

    /**
     * @param  Routes  $routesCollection
     * @param  array  $routes
     * @return void
     */
    protected function bindRoutes(Routes $routesCollection, array $routes): void
    {
        foreach ($routes as $routeName => $consumer) {
            $route = $routesCollection->route($routeName);

            $routeHandlers = $this->determineConsumers($consumer);

            if (count($routeHandlers) > 0) {
                $route->to(...$routeHandlers);
            }

            if ($queue = $this->determineQueue($consumer)) {
                $route->onQueue($queue);
            }
        }
    }

    /**
     * @param  object|array|string  $config
     * @return array|string[]
     */
    protected function determineConsumers(object|array|string $config): array
    {
        return match (true) {
            // one handler
            is_string($config) || is_object($config) => [$config],

            // only handlers
            array_is_list($config) => $config,

            // event handlers can be empty
            default => Arr::wrap($config['consumers'] ?? [])
        };
    }

    /**
     * @param  object|array|string  $config
     * @return QueueFactory|null
     */
    protected function determineQueue(object|array|string $config): null|QueueFactory
    {
        if (! is_array($config) || ! isset($config['queue'])) {
            return null;
        }

        $queue = $config['queue'];

        return match (true) {
            is_string($queue) => $this->container[$queue],
            is_array($queue) => new QueueFactory(...$queue),
            default => null,
        };
    }
}
