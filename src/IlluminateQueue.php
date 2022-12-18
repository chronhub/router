<?php

declare(strict_types=1);

namespace Chronhub\Message\Router;

use Chronhub\Contracts\Message\Envelop;
use Chronhub\Contracts\Router\MessageQueue;
use Illuminate\Contracts\Bus\QueueingDispatcher;
use Chronhub\Contracts\Support\Serializer\MessageSerializer;

final class IlluminateQueue implements MessageQueue
{
    public function __construct(public readonly QueueingDispatcher $queueingDispatcher,
                                public readonly MessageSerializer $messageSerializer,
                                public readonly ?QueueFactory $queueFactory = null)
    {
    }

    public function toQueue(Envelop $message): void
    {
        if ($this->queueFactory && $message->hasNot('queue')) {
            $message = $message->withHeader('queue', $this->queueFactory->toArray());
        }

        $payload = $this->messageSerializer->serializeMessage($message);

        $this->queueingDispatcher->dispatchToQueue(new MessageJob($payload));
    }
}
