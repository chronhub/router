<?php

declare(strict_types=1);

namespace Chronhub\Message\Router\Tests\Unit;

use ValueError;
use Chronhub\Testing\UnitTestCase;
use Chronhub\Tracker\TrackMessage;
use Chronhub\Message\Router\Builder;
use Chronhub\Reporter\ReportCommand;
use Chronhub\Contracts\Message\Envelop;
use Chronhub\Contracts\Message\Decorator;
use Chronhub\Contracts\Reporter\Reporter;
use Chronhub\Message\Router\QueueFactory;
use Chronhub\Reporter\Subscribing\NoOpMessageSubscriber;
use Chronhub\Message\Router\Exceptions\RouterRuleViolation;

final class BuilderTest extends UnitTestCase
{
    /**
     * @test
     */
    public function it_assert_default_properites(): void
    {
        $builder = new Builder();

        $this->assertNull($builder->reporterConcrete());
        $this->assertNull($builder->reporterServiceId());
        $this->assertNull($builder->trackerId());
        $this->assertNull($builder->consumerMethodName());
        $this->assertNull($builder->producerServiceId());
        $this->assertNull($builder->queueFactory());
        $this->assertNull($builder->queueFactory());
        $this->assertEmpty($builder->messageDecorators());
        $this->assertEmpty($builder->messageSubscribers());
    }

    /**
     * @test
     */
    public function it_set_properties(): void
    {
        $builder = new Builder();

        $builder
            ->withReporterConcreteClass(ReportCommand::class)
            ->withReporterServiceId('reporter.default')
            ->withConsumerMethodName('command')
            ->withProducerStrategy('sync')
            ->withProducerServiceId('message.producer.id')
            ->withQueueFactory(new QueueFactory())
            ->withTrackerId(TrackMessage::class)
            ->withMessageDecorators(new class() implements Decorator
            {
                public function decorate(Envelop $message): Envelop
                {
                    return $message;
                }
            })
            ->withMessageSubscribers(
                new NoOpMessageSubscriber(Reporter::DISPATCH_EVENT, 1),
                new NoOpMessageSubscriber(Reporter::FINALIZE_EVENT, -1),
            );

        $this->assertEquals(ReportCommand::class, $builder->reporterConcrete());
        $this->assertEquals('reporter.default', $builder->reporterServiceId());
        $this->assertEquals(TrackMessage::class, $builder->trackerId());
        $this->assertEquals('command', $builder->consumerMethodName());
        $this->assertEquals('sync', $builder->producerStrategy());
        $this->assertEquals('message.producer.id', $builder->producerServiceId());
        $this->assertInstanceOf(QueueFactory::class, $builder->queueFactory());
        $this->assertCount(1, $builder->messageDecorators());
        $this->assertCount(2, $builder->messageSubscribers());
    }

    /**
     * @test
     */
    public function it_set_group_queue_options_as_empty_array_for_defulat_queue(): void
    {
        $builder = new Builder();

        $this->assertNull($builder->queueFactory());

        $builder->withQueueFactory([]);

        $this->assertInstanceOf(QueueFactory::class, $builder->queueFactory());
    }

    /**
     * @test
     */
    public function it_set_group_queue_options_as_array(): void
    {
        $builder = new Builder();

        $this->assertNull($builder->queueFactory());

        $builder->withQueueFactory(['connection' => 'rabbitmq', 'name' => 'default']);

        $this->assertInstanceOf(QueueFactory::class, $builder->queueFactory());
        $this->assertEquals('rabbitmq', $builder->queueFactory()->connection);
        $this->assertEquals('default', $builder->queueFactory()->name);
    }

    /**
     * @test
     */
    public function it_set_group_queue_options_as_queue_factory(): void
    {
        $builder = new Builder();

        $this->assertNull($builder->queueFactory());

        $builder->withQueueFactory(new QueueFactory(tries: 2, maxExceptions: 24));

        $this->assertInstanceOf(QueueFactory::class, $builder->queueFactory());
        $this->assertEquals(2, $builder->queueFactory()->tries);
        $this->assertEquals(24, $builder->queueFactory()->maxExceptions);
    }

    /**
     * @test
     */
    public function it_raise_exception_when_reporter_class_is_not_a_valid_class_name(): void
    {
        $this->expectException(RouterRuleViolation::class);
        $this->expectExceptionMessage('Reporter concrete class reporter.command.default must be an instance of '.Reporter::class);

        $builder = new Builder();

        $builder->withReporterConcreteClass('reporter.command.default');
    }

    /**
     * @test
     */
    public function it_merge_message_decorators_as_string_or_instance(): void
    {
        $group = new Builder();

        $this->assertEmpty($group->messageDecorators());

        $group->withMessageDecorators(new class implements Decorator
        {
            public function decorate(Envelop $message): Envelop
            {
                return $message;
            }
        });

        $this->assertCount(1, $group->messageDecorators());

        $group->withMessageDecorators(new class implements Decorator
        {
            public function decorate(Envelop $message): Envelop
            {
                return $message;
            }
        });

        $this->assertCount(2, $group->messageDecorators());

        $group->withMessageDecorators('someMessageDecorator');

        $this->assertCount(3, $group->messageDecorators());
    }

    /**
     * @test
     */
    public function it_merge_message_subscribers_as_string_or_instance(): void
    {
        $builder = new Builder();

        $this->assertEmpty($builder->messageSubscribers());

        $builder->withMessageSubscribers(new NoOpMessageSubscriber(Reporter::DISPATCH_EVENT, 1));

        $this->assertCount(1, $builder->messageSubscribers());

        $builder->withMessageSubscribers(new NoOpMessageSubscriber(Reporter::DISPATCH_EVENT, 10));

        $this->assertCount(2, $builder->messageSubscribers());

        $builder->withMessageSubscribers('some.subscriber.id');

        $this->assertCount(3, $builder->messageSubscribers());
    }

    /**
     * @test
     */
    public function it_raise_exception_when_set_producer_key_is_unknown(): void
    {
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('"unknown_strategy" is not a valid backing value for enum "Chronhub\Message\Router\ProducerStrategy"');

        $builder = new Builder();

        $builder->withProducerStrategy('unknown_strategy');
    }

    /**
     * @test
     */
    public function it_raise_exception_when_accessingnull_producer_startegy(): void
    {
        $this->expectException(RouterRuleViolation::class);
        $this->expectExceptionMessage('Producer strategy can not be null');

        $builder = new Builder();

        $builder->producerStrategy();
    }
}
