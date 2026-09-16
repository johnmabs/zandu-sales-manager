<?php

declare(strict_types=1);

namespace Zandu\Tests\Platform\Messaging;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\Platform\Messaging\ClaimedOutboxMessage;
use Zandu\Platform\Messaging\OutboxMessagePublisher;
use Zandu\Platform\Messaging\OutboxQueue;
use Zandu\Platform\Messaging\OutboxWorker;
use Zandu\Platform\Operations\OperationalMetrics;
use Zandu\SharedKernel\Identity\IdGenerator;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\OutboxMessageId;
use Zandu\SharedKernel\Identity\Uuid;
use Zandu\SharedKernel\Messaging\CorrelationId;
use Zandu\SharedKernel\Messaging\OutboxMessage;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final class OutboxWorkerTest extends TestCase
{
    private const ORGANIZATION = '0199b234-2000-7000-8000-000000000001';
    private const MESSAGE = '0199b234-2000-7000-8000-000000000002';
    private const CLAIM = '0199b234-2000-7000-8000-000000000003';
    private const CORRELATION = '0199b234-2000-7000-8000-000000000004';

    private SymfonyUuidFactory $uuidFactory;
    private OrganizationId $organizationId;
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->uuidFactory = new SymfonyUuidFactory();
        $this->organizationId = OrganizationId::fromString(self::ORGANIZATION, $this->uuidFactory);
        $this->now = new DateTimeImmutable('2026-09-16 12:00:00+00');
    }

    public function testItPublishesAndAcknowledgesAClaimedMessage(): void
    {
        $queue = new InMemoryOutboxQueue([$this->claimedMessage(0)]);
        $publisher = new RecordingPublisher();
        $metrics = new OperationalMetrics();
        $worker = $this->worker($queue, $metrics);

        $result = $worker->runBatch($this->organizationId, $publisher, 10);

        self::assertSame(1, $result->claimed);
        self::assertSame(1, $result->published);
        self::assertSame(0, $result->scheduledForRetry);
        self::assertCount(1, $publisher->published);
        self::assertCount(1, $queue->published);
        self::assertSame(10, $queue->lastLimit);
        self::assertEquals($this->now->modify('+30 seconds'), $queue->lastClaimedUntil);
    }

    public function testItSchedulesAPublishingFailureForRetry(): void
    {
        $queue = new InMemoryOutboxQueue([$this->claimedMessage(0)]);
        $metrics = new OperationalMetrics();
        $worker = $this->worker($queue, $metrics);

        $result = $worker->runBatch($this->organizationId, new RecordingPublisher(true));

        self::assertSame(1, $result->scheduledForRetry);
        self::assertSame(0, $result->deadLettered);
        self::assertFalse($queue->failures[0][0]);
        self::assertSame('transport unavailable', $queue->failures[0][1]);
        self::assertEquals($this->now->modify('+30 seconds'), $queue->failures[0][2]);
        self::assertSame(1, $metrics->snapshot()['outbox_publish_failures']);
        self::assertSame(1, $metrics->snapshot()['worker_retry_count']);
    }

    public function testItDeadLettersTheFailureAtTheAttemptThreshold(): void
    {
        $queue = new InMemoryOutboxQueue([$this->claimedMessage(4)]);
        $metrics = new OperationalMetrics();
        $worker = $this->worker($queue, $metrics);

        $result = $worker->runBatch($this->organizationId, new RecordingPublisher(true));

        self::assertSame(0, $result->scheduledForRetry);
        self::assertSame(1, $result->deadLettered);
        self::assertTrue($queue->failures[0][0]);
        self::assertSame(1, $metrics->snapshot()['dead_letter_count']);
    }

    private function worker(InMemoryOutboxQueue $queue, OperationalMetrics $metrics): OutboxWorker
    {
        return new OutboxWorker(
            $queue,
            new PassthroughTenantTransaction(),
            new FixedIdGenerator($this->uuidFactory->fromString(self::CLAIM)),
            new FixedClock($this->now),
            $metrics,
        );
    }

    private function claimedMessage(int $attempts): ClaimedOutboxMessage
    {
        return new ClaimedOutboxMessage(
            new OutboxMessage(
                OutboxMessageId::fromString(self::MESSAGE, $this->uuidFactory),
                $this->organizationId,
                'TestEvent',
                ['value' => 'test'],
                CorrelationId::fromString(self::CORRELATION, $this->uuidFactory),
                null,
                $this->now,
            ),
            $this->uuidFactory->fromString(self::CLAIM),
            $attempts,
        );
    }
}

/** @internal */
final class InMemoryOutboxQueue implements OutboxQueue
{
    /** @var list<ClaimedOutboxMessage> */
    public array $published = [];

    /** @var list<array{bool, string, DateTimeImmutable}> */
    public array $failures = [];

    public int $lastLimit = 0;
    public ?DateTimeImmutable $lastClaimedUntil = null;

    /** @param list<ClaimedOutboxMessage> $claimed */
    public function __construct(private readonly array $claimed) {}

    public function claimBatch(
        OrganizationId $organizationId,
        Uuid $claimId,
        DateTimeImmutable $now,
        DateTimeImmutable $claimedUntil,
        int $limit,
    ): array {
        $this->lastLimit = $limit;
        $this->lastClaimedUntil = $claimedUntil;

        return $this->claimed;
    }

    public function markPublished(ClaimedOutboxMessage $claimed, DateTimeImmutable $publishedAt): void
    {
        $this->published[] = $claimed;
    }

    public function markFailed(
        ClaimedOutboxMessage $claimed,
        DateTimeImmutable $availableAt,
        string $error,
        bool $deadLetter,
    ): void {
        $this->failures[] = [$deadLetter, $error, $availableAt];
    }
}

/** @internal */
final class RecordingPublisher implements OutboxMessagePublisher
{
    /** @var list<OutboxMessage> */
    public array $published = [];

    public function __construct(private readonly bool $fail = false) {}

    public function publish(OutboxMessage $message): void
    {
        if ($this->fail) {
            throw new RuntimeException('transport unavailable');
        }

        $this->published[] = $message;
    }
}

/** @internal */
final readonly class PassthroughTenantTransaction implements TenantTransaction
{
    public function transactional(OrganizationId $organizationId, callable $operation): mixed
    {
        return $operation();
    }
}

/** @internal */
final readonly class FixedIdGenerator implements IdGenerator
{
    public function __construct(private Uuid $uuid) {}

    public function generate(): Uuid
    {
        return $this->uuid;
    }
}

/** @internal */
final readonly class FixedClock implements Clock
{
    public function __construct(private DateTimeImmutable $now) {}

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }
}
