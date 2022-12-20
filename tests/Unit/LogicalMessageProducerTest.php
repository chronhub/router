<?php

declare(strict_types=1);

namespace Chronhub\Message\Router\Tests\Unit;

use stdClass;
use Generator;
use ValueError;
use InvalidArgumentException;
use Chronhub\Testing\UnitTest;
use Chronhub\Contracts\Message\Header;
use Chronhub\Testing\Double\SomeEvent;
use Chronhub\Contracts\Message\Envelop;
use Chronhub\Testing\Stubs\MessageStub;
use Chronhub\Testing\Double\SomeCommand;
use Chronhub\Message\Router\ProducerStrategy;
use Chronhub\Testing\Double\SomeAsyncCommand;
use Chronhub\Message\Router\LogicalMessageProducer;

final class LogicalMessageProducerTest extends UnitTest
{
    /**
     * @test
     * @dataProvider provideSyncMessage
     */
    public function it_assert_message_is_sync(Envelop $message): void
    {
        $unity = new LogicalMessageProducer();

        $this->assertTrue($unity->isSync($message));
    }

    /**
     * @test
     * @dataProvider provideAsyncMessage
     */
    public function it_assert_message_is_async(Envelop $message): void
    {
        $unity = new LogicalMessageProducer();

        $this->assertFalse($unity->isSync($message));
    }

    /**
     * @test
     * @dataProvider provideInvalidEventDispatchedHeader
     */
    public function it_raise_exception_when_event_dispatched_header_is_invalid(?array $headers): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid producer event header: "__event_dispatched" is required and must be a boolean');

        $unity = new LogicalMessageProducer();

        $unity->isSync(new MessageStub(new SomeEvent([]), $headers ?? []));
    }

    /**
     * @test
     * @dataProvider provideInvalidEventStrategydHeader
     */
    public function it_raise_exception_when_event_strategy_header_is_invalid(array $headers): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid producer event header: "__event_strategy" is required and must be a string');

        $unity = new LogicalMessageProducer();

        $unity->isSync(new MessageStub(new SomeEvent([]), $headers));
    }

    /**
     * @test
     */
    public function it_raise_exception_when_event_strategy_header_is_not_part_of_producer_strategy(): void
    {
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('"invalid_strategy" is not a valid backing value for enum "Chronhub\Message\Router\ProducerStrategy"');

        $unity = new LogicalMessageProducer();

        $unity->isSync(new MessageStub(new SomeEvent([]), [
            Header::EVENT_DISPATCHED => true,
            Header::EVENT_STRATEGY => 'invalid_strategy',
        ]));
    }

    public function provideSyncMessage(): Generator
    {
        $someEvent = SomeCommand::fromContent(['name' => 'steph bug']);

        yield [new MessageStub($someEvent, [
            Header::EVENT_DISPATCHED => false,
            Header::EVENT_STRATEGY => ProducerStrategy::SYNC->value,
        ])];

        yield [new MessageStub($someEvent, [
            Header::EVENT_DISPATCHED => true,
            Header::EVENT_STRATEGY => ProducerStrategy::ASYNC->value,
        ])];

        yield [new MessageStub($someEvent, [
            Header::EVENT_DISPATCHED => false,
            Header::EVENT_STRATEGY => ProducerStrategy::PER_MESSAGE->value,
        ])];

        yield [new MessageStub($someEvent, [
            Header::EVENT_DISPATCHED => true,
            Header::EVENT_STRATEGY => ProducerStrategy::PER_MESSAGE->value,
        ])];

        yield [new MessageStub(SomeAsyncCommand::fromContent(['foo' => 'bar']), [
            Header::EVENT_DISPATCHED => true,
            Header::EVENT_STRATEGY => ProducerStrategy::PER_MESSAGE->value,
        ])];
    }

    public function provideAsyncMessage(): Generator
    {
        $someEvent = SomeAsyncCommand::fromContent(['name' => 'steph bug']);

        yield [new MessageStub($someEvent, [
            Header::EVENT_DISPATCHED => false,
            Header::EVENT_STRATEGY => ProducerStrategy::ASYNC->value,
        ])];

        yield [new MessageStub(SomeAsyncCommand::fromContent(['foo' => 'bar']), [
            Header::EVENT_DISPATCHED => false,
            Header::EVENT_STRATEGY => ProducerStrategy::PER_MESSAGE->value,
        ])];
    }

    public function provideInvalidEventDispatchedHeader(): Generator
    {
        yield [null];
        yield [[Header::EVENT_DISPATCHED => 1]];
        yield [[Header::EVENT_DISPATCHED => 'dispatched']];
    }

    public function provideInvalidEventStrategydHeader(): Generator
    {
        yield [[
            Header::EVENT_DISPATCHED => false,
            Header::EVENT_STRATEGY => 1,
        ]];

        yield [[
            Header::EVENT_DISPATCHED => true,
            Header::EVENT_STRATEGY => new stdClass(),
        ]];
    }
}
