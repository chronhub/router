<?php

declare(strict_types=1);

namespace Chronhub\Message\Router;

use Chronhub\Contracts\Message\Decorator;
use Chronhub\Contracts\Reporter\Reporter;
use Chronhub\Contracts\Tracker\MessageSubscriber;
use Chronhub\Message\Router\Exceptions\RouterRuleViolation;
use function is_a;
use function is_array;
use function array_merge;

final class Builder
{
    /**
     * Reporter service id
     *
     * Use to register the reporter in ioc
     *
     * @var string|null
     */
    private ?string $reporterServiceId = null;

    /**
     * Reporter concrete class name
     *
     * will be used as default binding if reporter service id is not set
     *
     * @var string|null
     */
    private ?string $reporterConcrete = null;

    /**
     * Tracker service id
     *
     * @var string|null
     */
    private ?string $messageTrackerId = null;

    /**
     * Message handler method name
     *
     * default null for __invoke magic method
     *
     * @var string|null
     */
    private ?string $consumerMethodName = null;

    /**
     * Message producer strategy key
     *
     * available sync, per_message or async
     *
     * @var string|null
     */
    private ?string $producerStrategy = null;

    /**
     * Message producer sevice id
     *
     * @var string|null
     */
    private ?string $producerServiceId = null;

    /**
     * Group queue factory
     *
     * @var QueueFactory|null
     */
    private ?QueueFactory $queueFactory = null;

    /**
     * Append Message Decorators
     *
     * @var array<string|Decorator>
     */
    private array $messageDecorators = [];

    /**
     * Append message subscribers
     *
     * @var array<string|MessageSubscriber>
     */
    private array $messageSubscribers = [];

    public function reporterServiceId(): ?string
    {
        return $this->reporterServiceId;
    }

    public function withReporterServiceId(string $reporterServiceId): self
    {
        $this->reporterServiceId = $reporterServiceId;

        return $this;
    }

    public function reporterConcrete(): ?string
    {
        return $this->reporterConcrete;
    }

    public function withReporterConcreteClass(string $reporterConcrete): self
    {
        if (! is_a($reporterConcrete, Reporter::class, true)) {
            throw new RouterRuleViolation("Reporter concrete class $reporterConcrete must be an instance of ".Reporter::class);
        }

        $this->reporterConcrete = $reporterConcrete;

        return $this;
    }

    public function trackerId(): ?string
    {
        return $this->messageTrackerId;
    }

    public function withTrackerId(string $trackerId): self
    {
        $this->messageTrackerId = $trackerId;

        return $this;
    }

    public function consumerMethodName(): ?string
    {
        return $this->consumerMethodName;
    }

    public function withConsumerMethodName(string $consumerMethodName): self
    {
        $this->consumerMethodName = $consumerMethodName;

        return $this;
    }

    public function messageDecorators(): array
    {
        return $this->messageDecorators;
    }

    public function withMessageDecorators(string|Decorator ...$messageDecorators): self
    {
        $this->messageDecorators = array_merge($this->messageDecorators, $messageDecorators);

        return $this;
    }

    public function messageSubscribers(): array
    {
        return $this->messageSubscribers;
    }

    public function withMessageSubscribers(string|MessageSubscriber ...$messageSubscribers): self
    {
        $this->messageSubscribers = array_merge($this->messageSubscribers, $messageSubscribers);

        return $this;
    }

    public function producerStrategy(): string
    {
        if ($this->producerStrategy === null) {
            throw new RouterRuleViolation('Producer strategy can not be null');
        }

        return $this->producerStrategy;
    }

    public function withProducerStrategy(string $producerStrategy): self
    {
        $this->producerStrategy = ProducerStrategy::from($producerStrategy)->value;

        return $this;
    }

    public function producerServiceId(): ?string
    {
        return $this->producerServiceId;
    }

    public function withProducerServiceId(string $producerServiceId): self
    {
        $this->producerServiceId = $producerServiceId;

        return $this;
    }

    public function queueFactory(): ?QueueFactory
    {
        return $this->queueFactory;
    }

    public function withQueueFactory(QueueFactory|array $queueFactory): self
    {
        if (is_array($queueFactory)) {
            $queueFactory = new QueueFactory(...$queueFactory);
        }

        $this->queueFactory = $queueFactory;

        return $this;
    }
}
