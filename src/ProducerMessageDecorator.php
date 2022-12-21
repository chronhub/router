<?php

declare(strict_types=1);

namespace Chronhub\Message\Router;

use Chronhub\Contracts\Message\Header;
use Chronhub\Contracts\Message\Envelop;
use Chronhub\Contracts\Message\Decorator;

final class ProducerMessageDecorator implements Decorator
{
    public function __construct(private readonly ProducerStrategy $producerStrategy)
    {
    }

    public function decorate(Envelop $message): Envelop
    {
        if ($message->hasNot(Header::EVENT_STRATEGY)) {
            $message = $message->withHeader(Header::EVENT_STRATEGY, $this->producerStrategy->value);
        }

        if ($message->hasNot(Header::EVENT_DISPATCHED)) {
            $message = $message->withHeader(Header::EVENT_DISPATCHED, false);
        }

        return $message;
    }
}
