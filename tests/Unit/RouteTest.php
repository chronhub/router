<?php

declare(strict_types=1);

namespace Chronhub\Message\Router\Tests\Unit;

use Generator;
use Chronhub\Message\Router\Route;
use Chronhub\Testing\UnitTestCase;
use Chronhub\Message\Router\QueueFactory;
use Chronhub\Testing\Double\Message\SomeEvent;
use Chronhub\Testing\Double\Message\SomeQuery;
use Chronhub\Testing\Double\Message\SomeCommand;
use Chronhub\Message\Router\Exceptions\RouterRuleViolation;
use function array_shift;

final class RouteTest extends UnitTestCase
{
    /**
     * @test
     * @dataProvider provideMessageName
     */
    public function it_construct_with_message_name(string $messageName): void
    {
        $route = new Route($messageName);

        $this->assertEquals($messageName, $route->name());
        $this->assertNull($route->queue());
        $this->assertEmpty($route->consumers());
    }

    /**
     * @test
     */
    public function it_raise_exception_when_message_name_is_not_a_valid_class_name(): void
    {
        $this->expectException(RouterRuleViolation::class);
        $this->expectExceptionMessage('Message name must be a valid class name, got foo');

        new Route('foo');
    }

    /**
     * @test
     * @dataProvider provideMessageName
     */
    public function it_add_consumer(string $messageName): void
    {
        $route = new Route($messageName);

        $this->assertEmpty($route->consumers());

        $handler = static function (): void {
            //
        };

        $route->to($handler);

        $this->assertCount(1, $route->consumers());

        $consumers = $route->consumers();
        $this->assertSame($handler, array_shift($consumers));
    }

    /**
     * @test
     * @dataProvider provideMessageName
     */
    public function it_merge_consumers(string $messageName): void
    {
        $route = new Route($messageName);

        $this->assertEmpty($route->consumers());

        $route->to(static function (): void {
            //
        });

        $this->assertCount(1, $route->consumers());

        $route->to(static function (): void {
            //
        });

        $this->assertCount(2, $route->consumers());
    }

    /**
     * @test
     */
    public function it_set_route_queue_option_as_queue_factory_instance(): void
    {
        $route = new Route(SomeCommand::class);

        $this->assertNull($route->queue());

        $queueFactory = new QueueFactory();

        $route->onQueue($queueFactory);

        $this->assertSame($queueFactory, $route->queue());
    }

    /**
     * @test
     */
    public function it_set_default_queue_option_as_empty_array(): void
    {
        $route = new Route(SomeEvent::class);

        $this->assertNull($route->queue());

        $route->onQueue([]);

        $this->assertEquals(new QueueFactory(), $route->queue());
    }

    /**
     * @test
     */
    public function it_override_route_queue_option_with_associative_array(): void
    {
        $route = new Route(SomeCommand::class);

        $this->assertNull($route->queue());

        $route->onQueue(['connection' => 'redis', 'name' => 'transaction']);

        $this->assertNotEquals(new QueueFactory(), $route->queue());

        $this->assertInstanceOf(QueueFactory::class, $route->queue());
        $this->assertEquals('redis', $route->queue()->connection);
        $this->assertEquals('transaction', $route->queue()->name);
    }

    public function provideMessageName(): Generator
    {
        yield [SomeCommand::class];
        yield [SomeEvent::class];
        yield [SomeQuery::class];
    }
}
