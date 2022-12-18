<?php

declare(strict_types=1);

namespace Chronhub\Message\Router\Tests\Unit;

use Chronhub\Testing\UnitTest;
use Chronhub\Message\Router\Routes;
use Chronhub\Testing\Double\SomeEvent;
use Chronhub\Testing\Double\SomeCommand;
use Chronhub\Testing\Double\AnotherCommand;
use Chronhub\Message\Router\Exceptions\RouterRuleViolation;

final class RoutesTest extends UnitTest
{
    /**
     * @test
     */
    public function it_instantiate_with_empty_route_collection(): void
    {
        $routeCollection = new Routes();

        $this->assertEmpty($routeCollection->routes());
    }

    /**
     * @test
     */
    public function it_add_route_with_concrete_name_given(): void
    {
        $routes = new Routes();

        $route = $routes->route(SomeCommand::class);

        $this->assertEquals(SomeCommand::class, $route->name());

        $this->assertCount(1, $routes->routes());
        $this->assertEquals($route, $routes->routes()->first());
    }

    /**
     * @test
     */
    public function it_find_route_by_name(): void
    {
        $routes = new Routes();

        $route1 = $routes->route(SomeCommand::class);
        $route2 = $routes->route(AnotherCommand::class);

        $this->assertEquals($route1, $routes->match(SomeCommand::class));
        $this->assertEquals($route2, $routes->match(AnotherCommand::class));
    }

    /**
     * @test
     */
    public function it_return_null_when_route_does_not_match(): void
    {
        $routes = new Routes();

        $routes->route(SomeCommand::class);

        $this->assertNull($routes->match(SomeEvent::class));
    }

    /**
     * @test
     */
    public function it_raise_exception_when_route_is_duplicate(): void
    {
        $this->expectException(RouterRuleViolation::class);
        $this->expectExceptionMessage('Message name already exists: '.SomeCommand::class);

        $routes = new Routes();

        $routes->route(SomeCommand::class);
        $routes->route(SomeCommand::class);

        $routes->match(SomeCommand::class);
    }
}
