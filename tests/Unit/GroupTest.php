<?php

declare(strict_types=1);

namespace Chronhub\Message\Router\Tests\Unit;

use Generator;
use Chronhub\Testing\UnitTest;
use Chronhub\Message\Router\Group;
use Chronhub\Message\Router\Route;
use Chronhub\Tracker\TrackMessage;
use Chronhub\Message\Router\Routes;
use Chronhub\Reporter\ReportCommand;
use Chronhub\Contracts\Message\Envelop;
use Chronhub\Testing\Double\SomeCommand;
use Chronhub\Contracts\Message\Decorator;
use Chronhub\Contracts\Reporter\Reporter;
use Chronhub\Message\Router\QueueFactory;
use Chronhub\Reporter\Subscribing\NoOpMessageSubscriber;

final class GroupTest extends UnitTest
{
    private Group $group;

    private Routes $routes;

    protected function setUp(): void
    {
        $this->group = new Group('command', 'default', new Routes());

        $this->routes = $this->group->routes();

        $this->assertEmpty($this->routes->routes());

        $this->assertNull($this->routes->match(SomeCommand::class));
    }

    /**
     * @test
     */
    public function it_instantiate_group(): void
    {
        $this->assertEquals('command', $this->group->getType());
        $this->assertEquals('default', $this->group->getName());
    }

    /**
     * @test
     */
    public function it_configure_group(): void
    {
        $factory = $this->group->builder();

        $this->assertNull($factory->reporterConcrete());
        $this->assertNull($factory->reporterServiceId());
        $this->assertNull($factory->trackerId());
        $this->assertNull($factory->consumerMethodName());
        $this->assertEmpty($factory->messageDecorators());
        $this->assertEmpty($factory->messageSubscribers());

        $decorator = new class implements Decorator
        {
            public function decorate(Envelop $message): Envelop
            {
                return $message;
            }
        };

        $factory
            ->withReporterConcreteClass(ReportCommand::class)
            ->withConsumerMethodName('command')
            ->withProducerStrategy('sync')
            ->withTrackerId(TrackMessage::class)
            ->withMessageDecorators($decorator)
            ->withMessageSubscribers(
                new NoOpMessageSubscriber(Reporter::DISPATCH_EVENT, 1),
                new NoOpMessageSubscriber(Reporter::FINALIZE_EVENT, -1),
            );

        $this->assertEquals(ReportCommand::class, $factory->reporterConcrete());
        $this->assertNull($factory->reporterServiceId());
        $this->assertEquals(TrackMessage::class, $factory->trackerId());
        $this->assertEquals('command', $factory->consumerMethodName());
        $this->assertEquals('sync', $factory->producerStrategy());
        $this->assertCount(1, $factory->messageDecorators());
        $this->assertCount(2, $factory->messageSubscribers());
    }

    /**
     * @test
     */
    public function it_chain_routes_with_consumer(): void
    {
        $this->routes->route(SomeCommand::class)->to(function (): int {
            return 42;
        });

        $route = $this->routes->match(SomeCommand::class);

        $this->assertInstanceOf(Route::class, $route);

        $this->assertEquals(SomeCommand::class, $route->name());

        $this->assertEquals(42, $route->consumers()[0]());
    }

    /**
     * @test
     * @dataProvider provideDefaultQueueFactory
     */
    public function it_add_route_with_the_default_queue_factory(QueueFactory|array $queueFactory): void
    {
        $this->routes->route(SomeCommand::class)->to(function (): int {
            return 42;
        })->onQueue($queueFactory);

        $route = $this->routes->match(SomeCommand::class);

        $this->assertInstanceOf(Route::class, $route);

        $this->assertEquals(SomeCommand::class, $route->name());

        $this->assertEquals(42, $route->consumers()[0]());
        $this->assertEquals(new QueueFactory(), $route->queue());
    }

    /**
     * @test
     */
    public function it_add_route_with_overriden_parameters_queue(): void
    {
        $this->routes->route(SomeCommand::class)->to(function (): int {
            return 42;
        })->onQueue(['connection' => 'rabbitmq', 'name' => 'transaction-withdraw']);

        $route = $this->routes->match(SomeCommand::class);

        $this->assertInstanceOf(Route::class, $route);

        $this->assertEquals(SomeCommand::class, $route->name());

        $this->assertEquals(42, $route->consumers()[0]());

        $this->assertEquals('rabbitmq', $route->queue()->connection);
        $this->assertEquals('transaction-withdraw', $route->queue()->name);
    }

    public function provideDefaultQueueFactory(): Generator
    {
        yield [[]];
        yield [new QueueFactory()];
    }
}
