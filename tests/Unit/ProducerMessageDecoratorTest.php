<?php

declare(strict_types=1);

namespace Chronhub\Message\Router\Tests\Unit;

use Generator;
use Chronhub\Testing\UnitTestCase;
use Chronhub\Contracts\Message\Header;
use Chronhub\Testing\Stubs\MessageStub;
use Chronhub\Message\Router\ProducerStrategy;
use Chronhub\Testing\Double\Message\SomeCommand;
use Chronhub\Message\Router\ProducerMessageDecorator;

final class ProducerMessageDecoratorTest extends UnitTestCase
{
    /**
     * @test
     * @dataProvider provideProducerStrategy
     */
    public function it_decorate_message_with_event_strategy_and_dipatched_headers(ProducerStrategy $producerStrategy): void
    {
        $message = new MessageStub(SomeCommand::fromContent(['name' => 'steph bug']));

        $messageDecorator = new ProducerMessageDecorator($producerStrategy);

        $decoratedMessage = $messageDecorator->decorate($message);

        $this->assertNotEquals($message, $decoratedMessage);

        $this->assertEquals([
            Header::EVENT_STRATEGY => $producerStrategy->value,
            Header::EVENT_DISPATCHED => false,
        ], $decoratedMessage->headers());
    }

    public function provideProducerStrategy(): Generator
    {
        yield [ProducerStrategy::SYNC];
        yield [ProducerStrategy::ASYNC];
        yield [ProducerStrategy::PER_MESSAGE];
    }
}
