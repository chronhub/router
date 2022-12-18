<?php

declare(strict_types=1);

namespace Chronhub\Message\Router\Tests\Unit;

use Chronhub\Testing\ProphecyTest;
use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\Queue;
use Chronhub\Contracts\Message\Header;
use Chronhub\Message\Router\MessageJob;
use Chronhub\Testing\Double\SomeCommand;
use Chronhub\Contracts\Reporter\Reporter;

final class MessageJobTest extends ProphecyTest
{
    /**
     * @test
     */
    public function it_can_be_constructed_with_default_options(): void
    {
        $job = new MessageJob([]);

        $this->assertEquals(1, $job->tries);
        $this->assertEquals(3, $job->maxExceptions);
        $this->assertEquals(30, $job->timeout);
        $this->assertNull($job->connection);
        $this->assertNull($job->queue);
        $this->assertNull($job->delay);
    }

    /**
     * @test
     */
    public function it_can_be_constructed_with_options(): void
    {
        $payload = [
            'headers' => [
                'queue' => [
                    'connection' => 'redis',
                    'name' => 'default',
                    'tries' => 5,
                    'max_exceptions' => 1,
                    'timeout' => 60,
                    'delay' => 60,
                ],
            ],
        ];

        $job = new MessageJob($payload);

        $this->assertEquals(5, $job->tries);
        $this->assertEquals(1, $job->maxExceptions);
        $this->assertEquals(60, $job->timeout);
        $this->assertEquals('redis', $job->connection);
        $this->assertEquals('default', $job->queue);
        $this->assertEquals(60, $job->delay);
    }

    /**
     * @test
     */
    public function it_display_event_name(): void
    {
        $job = new MessageJob(['headers' => [Header::EVENT_TYPE => SomeCommand::class]]);

        $this->assertEquals(SomeCommand::class, $job->displayName());
    }

    /**
     * @test
     */
    public function it_queue_job(): void
    {
        $payload = [
            'headers' => [
                'queue' => [
                    'name' => 'account',
                ],
            ],
        ];

        $job = new MessageJob($payload);

        $laravelQueue = $this->prophesize(Queue::class);
        $laravelQueue->pushOn('account', $job)->shouldBeCalledOnce();

        $job->queue($laravelQueue->reveal(), $job);
    }

    /**
     * @test
     */
    public function it_handle_job(): void
    {
        $payload = [
            'headers' => [
                Header::REPORTER_ID => 'reporter.command',
                'queue' => [
                    'name' => 'default',
                ],
            ],
        ];

        $container = Container::getInstance();
        $container->bind('reporter.command', function () use ($payload): Reporter {
            $reporter = $this->prophesize(Reporter::class);
            $reporter->relay($payload)->shouldBeCalledOnce();

            return $reporter->reveal();
        });

        $job = new MessageJob($payload);

        $job->handle($container);
    }
}
