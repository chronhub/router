<?php

declare(strict_types=1);

namespace Chronhub\Message\Router\Tests\Unit;

use Closure;
use stdClass;
use Generator;
use Chronhub\Testing\UnitTest;
use Chronhub\Message\Router\Group;
use Chronhub\Message\Router\Routes;
use Illuminate\Container\Container;
use Chronhub\Contracts\Message\Header;
use Chronhub\Message\Router\FindRoute;
use Chronhub\Testing\Stubs\MessageStub;
use Chronhub\Testing\Double\SomeCommand;
use Chronhub\Message\Router\QueueFactory;
use Chronhub\Message\Router\Exceptions\RouteNotFound;
use Chronhub\Message\Router\Exceptions\RouteHandlerNotSupported;

final class FindRouteTest extends UnitTest
{
    private SomeCommand $command;

    private MessageStub $message;

    private Group $group;

    private Container $container;

    public function setUp(): void
    {
        $this->container = Container::getInstance();
        $this->group = new Group('command', 'default', new Routes());
        $this->command = new SomeCommand(['name' => 'steph']);
        $this->message = new MessageStub($this->command, [Header::EVENT_TYPE => SomeCommand::class]);
    }

    /**
     * @test
     */
    public function it_route_message_to_an_invokable_handler(): void
    {
        $consumer = new class
        {
            public function __invoke(SomeCommand $command): void
            {
            }
        };

        $this->group->routes()->route(SomeCommand::class)->to($consumer);

        $router = new FindRoute($this->group, $this->container);

        $consumers = $router->route($this->message);

        $this->assertSame($consumer, $consumers->first());
    }

    /**
     * @test
     */
    public function it_route_message_to_a_string_handler_resolved_by_container(): void
    {
        $consumer = new class
        {
            public function __invoke(SomeCommand $command): void
            {
            }
        };

        $this->container->bind('foo', fn (): object => $consumer);

        $this->group->routes()->route(SomeCommand::class)->to('foo');

        $router = new FindRoute($this->group, $this->container);

        $consumers = $router->route($this->message);

        $this->assertEquals($consumer, $consumers->first());
    }

    /**
     * @test
     */
    public function it_route_message_to_a_callable_method(): void
    {
        $consumer = new class()
        {
            public function command(): int
            {
                return 42;
            }
        };

        $this->group->builder()->withConsumerMethodName('command');
        $this->group->routes()->route(SomeCommand::class)->to($consumer);

        $router = new FindRoute($this->group, $this->container);

        $consumers = $router->route($this->message);

        $this->assertInstanceOf(Closure::class, $consumers->first());
        $this->assertEquals(42, $consumers->first()());
    }

    /**
     * @test
     */
    public function it_raise_exception_when_consumer_is_not_callable(): void
    {
        $this->expectException(RouteHandlerNotSupported::class);
        $this->expectExceptionMessage('Message handler with name '.SomeCommand::class.' not supported');

        $consumer = new class()
        {
            public function command(): void
            {
                //
            }
        };

        $this->group->routes()->route(SomeCommand::class)->to($consumer);
        $this->assertNull($this->group->builder()->consumerMethodName());

        $router = new FindRoute($this->group, $this->container);

        $router->route($this->message);
    }

    /**
     * @test
     */
    public function it_raise_exception_when_message_not_found(): void
    {
        $this->expectException(RouteNotFound::class);
        $this->expectExceptionMessage('Message name '.SomeCommand::class.' not found');

        $router = new FindRoute($this->group, $this->container);

        $router->route($this->message);
    }

    /**
     * @test
     * @dataProvider provideQueue
     */
    public function it_access_queue_route_options(QueueFactory|array $queueFactory): void
    {
        $consumer = new class
        {
            public function __invoke(SomeCommand $command): void
            {
                //
            }
        };

        $this->group->routes()
            ->route(SomeCommand::class)
            ->to($consumer)
            ->onQueue($queueFactory);

        $router = new FindRoute($this->group, $this->container);

        $consumers = $router->route($this->message);
        $this->assertSame($consumer, $consumers->first());

        $this->assertIsArray($router->onQueue($this->message));
    }

    /**
     * @test
     */
    public function it_raise_exception_when_access_queue_route_options_with_unregistered_message(): void
    {
        $this->expectException(RouteNotFound::class);

        $router = new FindRoute($this->group, $this->container);

        $router->onQueue(new MessageStub(new stdClass(), [Header::EVENT_TYPE => stdClass::class]));
    }

    public function provideQueue(): Generator
    {
        yield [[]];
        yield [['connection' => 'rabbitmq', 'name' => 'redis']];
        yield [new QueueFactory()];
    }
}
