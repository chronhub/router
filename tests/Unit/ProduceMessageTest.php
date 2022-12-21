<?php

declare(strict_types=1);

namespace Chronhub\Message\Router\Tests\Unit;

use Prophecy\Argument;
use Chronhub\Contracts\Message\Header;
use Chronhub\Testing\ProphecyTestCase;
use Chronhub\Contracts\Message\Envelop;
use Chronhub\Testing\Stubs\MessageStub;
use Chronhub\Contracts\Router\MessageQueue;
use Chronhub\Message\Router\ProduceMessage;
use Chronhub\Contracts\Router\ProducerUnity;
use Chronhub\Testing\Double\Message\SomeCommand;

final class ProduceMessageTest extends ProphecyTestCase
{
    /**
     * @test
     */
    public function it_produce_message_sync(): void
    {
        $unity = $this->prophesize(ProducerUnity::class);
        $queue = $this->prophesize(MessageQueue::class);

        $message = new MessageStub(SomeCommand::fromContent(['foo' => 'bar']), [Header::EVENT_DISPATCHED => false]);

        $unity->isSync($message)->willReturn(true)->shouldBeCalledOnce();
        $queue->toQueue($message)->shouldNotBeCalled();

        $producer = new ProduceMessage($unity->reveal(), $queue->reveal());

        $dispatchedMessage = $producer->produce($message);

        $this->assertTrue($dispatchedMessage->header(Header::EVENT_DISPATCHED));
    }

    /**
     * @test
     */
    public function it_produce_message_async(): void
    {
        $unity = $this->prophesize(ProducerUnity::class);
        $queue = $this->prophesize(MessageQueue::class);

        $message = new MessageStub(SomeCommand::fromContent(['foo' => 'bar']), [Header::EVENT_DISPATCHED => false]);

        $unity->isSync($message)->willReturn(false)->shouldBeCalledOnce();

        $queue->toQueue(Argument::that(function (Envelop $message): Envelop {
            $this->assertTrue($message->header(Header::EVENT_DISPATCHED));

            return $message;
        }))->shouldBeCalledOnce();

        $producer = new ProduceMessage($unity->reveal(), $queue->reveal());

        $dispatchedMessage = $producer->produce($message);

        $this->assertTrue($dispatchedMessage->header(Header::EVENT_DISPATCHED));
    }
}
