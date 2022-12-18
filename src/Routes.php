<?php

declare(strict_types=1);

namespace Chronhub\Message\Router;

use Illuminate\Support\Collection;
use Chronhub\Message\Router\Exceptions\RouterRuleViolation;

final class Routes
{
    /**
     * @var Collection<Route>
     */
    private Collection $routes;

    public function __construct()
    {
        $this->routes = new Collection();
    }

    /**
     * Add route with given concrete name
     *
     * @param  string  $messageName
     * @return Route
     */
    public function route(string $messageName): Route
    {
        $filteredRoutes = $this->routes->filter(
            fn (Route $route): bool => $messageName === $route->name()
        );

        if ($filteredRoutes->isNotEmpty()) {
            $exceptionMessage = "Message name already exists: $messageName";

            throw new RouterRuleViolation($exceptionMessage);
        }

        $route = new Route($messageName);

        $this->routes->push($route);

        return $route;
    }

    /**
     * Find route by message name
     *
     * @param  string  $messageName
     * @return Route|null
     */
    public function match(string $messageName): ?Route
    {
        return $this->routes->filter(
            fn (Route $route): bool => $messageName === $route->name()
        )->first();
    }

    /**
     * @return Collection<Route>
     */
    public function routes(): Collection
    {
        return clone $this->routes;
    }
}
