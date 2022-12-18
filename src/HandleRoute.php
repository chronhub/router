<?php

declare(strict_types=1);

namespace Chronhub\Message\Router;

use Chronhub\Tracker\ProvideTracking;
use Chronhub\Reporter\OnDispatchPriority;
use Chronhub\Contracts\Router\RouteLocator;
use Chronhub\Contracts\Router\ProducerUnity;
use Chronhub\Contracts\Tracker\MessageStory;
use Chronhub\Contracts\Router\MessageProducer;
use Chronhub\Contracts\Tracker\MessageTracker;
use Chronhub\Contracts\Tracker\MessageSubscriber;
use function count;
use function is_array;

final class HandleRoute implements MessageSubscriber
{
    use ProvideTracking;

    public function __construct(private readonly RouteLocator $router,
                                private readonly MessageProducer $messageProducer,
                                private readonly ProducerUnity $unity)
    {
    }

    public function attachToReporter(MessageTracker $tracker): void
    {
        $this->onDispatchEvent($tracker, $this->withStory(), OnDispatchPriority::ROUTE->value);
    }

    private function withStory(): callable
    {
        return function (MessageStory $story): void {
            $message = $story->message();

            $isSync = $this->unity->isSync($message);

            if (! $isSync) {
                $queueOptions = $this->router->onQueue($message);

                if (is_array($queueOptions) && count($queueOptions) > 0) {
                    $message = $message->withHeader('queue', $queueOptions);
                }
            }

            $dispatchedMessage = $this->messageProducer->produce($message);

            $story->withMessage($dispatchedMessage);

            if ($isSync) {
                $story->withConsumers($this->router->route($dispatchedMessage));
            }
        };
    }
}
