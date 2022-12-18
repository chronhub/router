<?php

declare(strict_types=1);

namespace Chronhub\Message\Router;

use Chronhub\Contracts\Message\Header;
use Chronhub\Contracts\Message\Envelop;
use Chronhub\Contracts\Router\MessageQueue;
use Chronhub\Contracts\Router\ProducerUnity;
use Chronhub\Contracts\Router\MessageProducer;

final class ProduceMessage implements MessageProducer
{
    public function __construct(private readonly ProducerUnity $unity,
                                private readonly ?MessageQueue $enqueue)
    {
    }

    public function produce(Envelop $message): Envelop
    {
        $isSyncMessage = $this->unity->isSync($message);

        $message = $message->withHeader(Header::EVENT_DISPATCHED, true);

        if (! $isSyncMessage) {
            $this->enqueue->toQueue($message);
        }

        return $message;
    }
}
