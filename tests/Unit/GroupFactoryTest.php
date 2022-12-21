<?php

declare(strict_types=1);

namespace Chronhub\Message\Router\Tests\Unit;

use React\Promise\Deferred;
use Chronhub\Message\Router\Group;
use Chronhub\Reporter\ReportEvent;
use Chronhub\Reporter\ReportQuery;
use Chronhub\Testing\UnitTestCase;
use Chronhub\Tracker\TrackMessage;
use Chronhub\Message\Router\Routes;
use Illuminate\Container\Container;
use Chronhub\Reporter\ReportCommand;
use Chronhub\Message\Router\GroupFactory;
use Chronhub\Testing\Double\Message\SomeEvent;
use Chronhub\Testing\Double\Message\SomeQuery;
use Chronhub\Reporter\Subscribing\ConsumeEvent;
use Chronhub\Reporter\Subscribing\ConsumeQuery;
use Chronhub\Testing\Double\Message\SomeCommand;
use Chronhub\Reporter\Subscribing\ConsumeCommand;
use Chronhub\Testing\Double\Message\AnotherEvent;

final class GroupFactoryTest extends UnitTestCase
{
    /**
     * @test
     */
    public function it_assert_default_command_config(): void
    {
        $group = new Group('command', 'default', new Routes());

        $container = fn (): Container => Container::getInstance();

        $factory = new GroupFactory($container);

        $factory->make($group, $this->getConfig()['command']['default']);
        $builder = $group->builder();

        $this->assertEquals(ReportCommand::class, $builder->reporterConcrete());
        $this->assertEquals('per_message', $builder->producerStrategy());
        $this->assertEquals(TrackMessage::class, $builder->trackerId());
        $this->assertEquals([ConsumeCommand::class], $builder->messageSubscribers());

        $this->assertEmpty($builder->messageDecorators());
        $this->assertNull($builder->reporterServiceId());
        $this->assertNull($builder->producerServiceId());
        $this->assertNull($builder->queueFactory());
        $this->assertNull($builder->consumerMethodName());

        $route = $group->routes()->match(SomeCommand::class);

        $this->assertEquals(SomeCommand::class, $route->name());
        $this->assertEquals([static function (SomeCommand $command): void {
            //
        }], $route->consumers());
    }

    private function getConfig(): array
    {
        return [
            'command' => [

                'default' => [
                    'concrete' => ReportCommand::class,
                    'service_id' => null,
                    'strategy' => 'per_message',
                    'producer_service' => null,
                    'queue' => null,
                    'tracker_id' => TrackMessage::class,
                    'method_name' => null,
                    'message_subscribers' => [ConsumeCommand::class],
                    'message_decorators' => [],
                    'routes' => [
                        SomeCommand::class => static function (SomeCommand $command): void {
                            //
                        },
                    ],
                ],

                'command_with_service_id' => [
                    'concrete' => ReportCommand::class,
                    'service_id' => 'reporter.command.default',
                    'strategy' => 'sync',
                    'producer_service' => null,
                    'queue' => null,
                    'tracker_id' => TrackMessage::class,
                    'method_name' => null,
                    'message_subscribers' => [ConsumeCommand::class],
                    'message_decorators' => [],
                    'routes' => [
                        SomeCommand::class => static function (SomeCommand $command): void {
                            //
                        },
                    ],
                ],

                'command_with_group_queue' => [
                    'concrete' => ReportCommand::class,
                    'service_id' => null,
                    'strategy' => 'async',
                    'producer_service' => null,
                    'queue' => ['connection' => 'rabbitmq', 'name' => 'transaction'],
                    'tracker_id' => TrackMessage::class,
                    'method_name' => null,
                    'message_subscribers' => [ConsumeCommand::class],
                    'message_decorators' => [],
                    'routes' => [
                        SomeCommand::class => static function (SomeCommand $command): void {
                            //
                        },
                    ],
                ],

                'command_with_route_queue' => [
                    'concrete' => ReportCommand::class,
                    'service_id' => null,
                    'strategy' => 'async',
                    'producer_service' => null,
                    'queue' => null,
                    'tracker_id' => TrackMessage::class,
                    'method_name' => null,
                    'message_subscribers' => [ConsumeCommand::class],
                    'message_decorators' => [],
                    'routes' => [
                        SomeCommand::class => [
                            'queue' => ['connection' => 'redis', 'name' => 'withdraw'],
                            'consumers' => [SomeCommand::class => static function (SomeCommand $command): void {
                                //
                            }],
                        ],
                    ],
                ],

                'command_with_producer' => [
                    'concrete' => ReportCommand::class,
                    'service_id' => null,
                    'strategy' => 'async',
                    'producer_service' => 'message.producer.id',
                    'queue' => null,
                    'tracker_id' => TrackMessage::class,
                    'method_name' => null,
                    'message_subscribers' => [ConsumeCommand::class],
                    'message_decorators' => [],
                    'routes' => [
                        SomeCommand::class => static function (SomeCommand $command): void {
                            //
                        },
                    ],
                ],
            ],

            'event' => [
                'default' => [
                    'concrete' => ReportEvent::class,
                    'service_id' => null,
                    'strategy' => 'per_message',
                    'producer_service' => null,
                    'queue' => null,
                    'tracker_id' => TrackMessage::class,
                    'method_name' => 'onEvent',
                    'message_subscribers' => [ConsumeEvent::class],
                    'message_decorators' => [],
                    'routes' => [
                        SomeEvent::class => static function (SomeEvent $event): void {
                            //
                        },
                        AnotherEvent::class => [
                            new class()
                            {
                                public function onEvent(AnotherEvent $event): void
                                {
                                    //
                                }
                            },
                            static function (AnotherEvent $event): void {
                                //
                            },
                        ],

                    ],
                ],
            ],

            'query' => [

                'default' => [
                    'concrete' => ReportQuery::class,
                    'service_id' => null,
                    'strategy' => 'sync',
                    'producer_service' => null,
                    'queue' => null,
                    'tracker_id' => TrackMessage::class,
                    'method_name' => null,
                    'message_subscribers' => [ConsumeQuery::class],
                    'message_decorators' => [],
                    'routes' => [
                        SomeQuery::class => function (SomeQuery $query, Deferred $promise): void {
                            $promise->resolve(42);
                        },
                    ],
                ],
            ],
        ];
    }
}
