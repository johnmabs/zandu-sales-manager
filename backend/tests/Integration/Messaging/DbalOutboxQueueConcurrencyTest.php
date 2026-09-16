<?php

declare(strict_types=1);

namespace Zandu\Tests\Integration\Messaging;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\Platform\Messaging\ClaimedOutboxMessage;
use Zandu\Platform\Messaging\OutboxClaimLost;
use Zandu\Platform\Persistence\DbalOutboxQueue;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\Tests\Integration\PostgresTestCase;

final class DbalOutboxQueueConcurrencyTest extends PostgresTestCase
{
    private const ORGANIZATION = '0199b234-1000-7000-8000-000000000001';
    private const ACTOR = '0199b234-1000-7000-8000-000000000002';
    private const MESSAGE_A = '0199b234-1000-7000-8000-000000000011';
    private const MESSAGE_B = '0199b234-1000-7000-8000-000000000012';
    private const CLAIM_A = '0199b234-1000-7000-8000-000000000021';
    private const CLAIM_B = '0199b234-1000-7000-8000-000000000022';

    private SymfonyUuidFactory $uuidFactory;
    private OrganizationId $organizationId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->uuidFactory = new SymfonyUuidFactory();
        $this->organizationId = OrganizationId::fromString(self::ORGANIZATION, $this->uuidFactory);
        $this->connection->executeStatement(
            "INSERT INTO organization.organizations (id,name,status,country_code,default_currency,default_time_zone,default_locale,created_by,created_at,updated_by,updated_at,version) VALUES (?, 'Outbox worker tenant','ACTIVE','CG','XAF','Africa/Brazzaville','fr_CG',?,NOW(),?,NOW(),1)",
            [self::ORGANIZATION, self::ACTOR, self::ACTOR],
        );
        $this->insertMessage(self::MESSAGE_A, '2026-09-16 10:00:00+00');
        $this->insertMessage(self::MESSAGE_B, '2026-09-16 10:00:01+00');
    }

    protected function tearDown(): void
    {
        if ($this->connection->isTransactionActive()) {
            $this->connection->rollBack();
        }

        $this->connection->executeStatement(
            'DELETE FROM messaging.outbox_messages WHERE organization_id = ?',
            [self::ORGANIZATION],
        );
        $this->connection->executeStatement(
            'DELETE FROM organization.organizations WHERE id = ?',
            [self::ORGANIZATION],
        );

        parent::tearDown();
    }

    public function testTwoWorkersSkipEachOthersLockedMessages(): void
    {
        $workerA = $this->connection;
        $workerB = $this->secondConnection();
        $queueA = new DbalOutboxQueue($workerA, $this->uuidFactory);
        $queueB = new DbalOutboxQueue($workerB, $this->uuidFactory);
        $now = new DateTimeImmutable('2026-09-16 12:00:00+00');

        $this->beginTenantTransaction($workerA);

        try {
            $claimedA = $queueA->claimBatch(
                $this->organizationId,
                $this->uuidFactory->fromString(self::CLAIM_A),
                $now,
                $now->modify('+30 seconds'),
                1,
            );

            $this->beginTenantTransaction($workerB);
            $claimedB = $queueB->claimBatch(
                $this->organizationId,
                $this->uuidFactory->fromString(self::CLAIM_B),
                $now,
                $now->modify('+30 seconds'),
                1,
            );
            $workerB->commit();

            self::assertCount(1, $claimedA);
            self::assertCount(1, $claimedB);
            self::assertSame(self::MESSAGE_A, $claimedA[0]->message->id->toString());
            self::assertSame(self::MESSAGE_B, $claimedB[0]->message->id->toString());
        } finally {
            $workerA->rollBack();

            if ($workerB->isTransactionActive()) {
                $workerB->rollBack();
            }

            $workerB->close();
        }

        self::assertSame('PENDING', $this->messageStatus(self::MESSAGE_A));
        self::assertSame('PROCESSING', $this->messageStatus(self::MESSAGE_B));
    }

    public function testAttemptThresholdMovesTheClaimedMessageToFailed(): void
    {
        $queue = new DbalOutboxQueue($this->connection, $this->uuidFactory);
        $now = new DateTimeImmutable('2026-09-16 12:00:00+00');
        $this->beginTenantTransaction($this->connection);

        $claimed = $queue->claimBatch(
            $this->organizationId,
            $this->uuidFactory->fromString(self::CLAIM_A),
            $now,
            $now->modify('+30 seconds'),
            1,
        );
        self::assertCount(1, $claimed);

        $queue->markFailed($claimed[0], $now->modify('+30 seconds'), 'transport unavailable', true);
        $this->connection->commit();

        self::assertSame('FAILED', $this->messageStatus(self::MESSAGE_A));
    }

    public function testCrashAfterClaimBeforePublicationIsRecoveredAfterLeaseExpiry(): void
    {
        $this->connection->executeStatement(
            'DELETE FROM messaging.outbox_messages WHERE id = ?',
            [self::MESSAGE_B],
        );
        $claimedAt = new DateTimeImmutable('2026-09-16 12:00:00+00');
        $queue = new DbalOutboxQueue($this->connection, $this->uuidFactory);

        $abandonedClaim = $this->claimAndCommit($this->connection, $queue, self::CLAIM_A, $claimedAt);
        self::assertSame(self::MESSAGE_A, $abandonedClaim->message->id->toString());
        self::assertSame('PROCESSING', $this->messageStatus(self::MESSAGE_A));

        // The worker process disappears here: it neither publishes nor acknowledges.
        $recoveryConnection = $this->secondConnection();

        try {
            $recoveryQueue = new DbalOutboxQueue($recoveryConnection, $this->uuidFactory);
            $recoveredClaim = $this->claimAndCommit(
                $recoveryConnection,
                $recoveryQueue,
                self::CLAIM_B,
                $claimedAt->modify('+31 seconds'),
            );
            $publishedMessageIds = [$recoveredClaim->message->id->toString()];

            $this->beginTenantTransaction($recoveryConnection);
            $recoveryQueue->markPublished($recoveredClaim, $claimedAt->modify('+31 seconds'));
            $recoveryConnection->commit();
        } finally {
            if ($recoveryConnection->isTransactionActive()) {
                $recoveryConnection->rollBack();
            }

            $recoveryConnection->close();
        }

        self::assertSame([self::MESSAGE_A], $publishedMessageIds);
        self::assertSame('PUBLISHED', $this->messageStatus(self::MESSAGE_A));
    }

    public function testCrashAfterPublicationBeforeAcknowledgementCausesSafeRedelivery(): void
    {
        $this->connection->executeStatement(
            'DELETE FROM messaging.outbox_messages WHERE id = ?',
            [self::MESSAGE_B],
        );
        $claimedAt = new DateTimeImmutable('2026-09-16 12:00:00+00');
        $queue = new DbalOutboxQueue($this->connection, $this->uuidFactory);
        $firstClaim = $this->claimAndCommit($this->connection, $queue, self::CLAIM_A, $claimedAt);
        $publishedMessageIds = [$firstClaim->message->id->toString()];

        // Publication succeeded, then the worker process disappears before acknowledgement.
        $recoveryConnection = $this->secondConnection();

        try {
            $recoveryQueue = new DbalOutboxQueue($recoveryConnection, $this->uuidFactory);
            $recoveredClaim = $this->claimAndCommit(
                $recoveryConnection,
                $recoveryQueue,
                self::CLAIM_B,
                $claimedAt->modify('+31 seconds'),
            );
            $publishedMessageIds[] = $recoveredClaim->message->id->toString();

            $this->beginTenantTransaction($this->connection);

            try {
                $queue->markPublished($firstClaim, $claimedAt->modify('+31 seconds'));
                self::fail('The expired claim must not acknowledge a claim owned by another worker.');
            } catch (OutboxClaimLost) {
                $this->connection->rollBack();
            }

            $this->beginTenantTransaction($recoveryConnection);
            $recoveryQueue->markPublished($recoveredClaim, $claimedAt->modify('+31 seconds'));
            $recoveryConnection->commit();
        } finally {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }

            if ($recoveryConnection->isTransactionActive()) {
                $recoveryConnection->rollBack();
            }

            $recoveryConnection->close();
        }

        self::assertSame([self::MESSAGE_A, self::MESSAGE_A], $publishedMessageIds);
        self::assertSame('PUBLISHED', $this->messageStatus(self::MESSAGE_A));
    }

    private function beginTenantTransaction(Connection $connection): void
    {
        $connection->beginTransaction();
        $connection->executeStatement('SET LOCAL ROLE zandu_runtime');
        $connection->fetchOne(
            "SELECT set_config('app.organization_id', ?, true)",
            [self::ORGANIZATION],
        );
    }

    private function claimAndCommit(
        Connection $connection,
        DbalOutboxQueue $queue,
        string $claimId,
        DateTimeImmutable $now,
    ): ClaimedOutboxMessage {
        $this->beginTenantTransaction($connection);

        try {
            $claimed = $queue->claimBatch(
                $this->organizationId,
                $this->uuidFactory->fromString($claimId),
                $now,
                $now->modify('+30 seconds'),
                1,
            );
            self::assertCount(1, $claimed);
            $connection->commit();

            return $claimed[0];
        } catch (\Throwable $exception) {
            $connection->rollBack();

            throw $exception;
        }
    }

    private function insertMessage(string $id, string $occurredAt): void
    {
        $this->connection->executeStatement(<<<'SQL'
INSERT INTO messaging.outbox_messages (
    id, organization_id, type, payload, correlation_id, occurred_at, available_at
) VALUES (?, ?, 'TestEvent', '{}', ?, ?, ?)
SQL, [$id, self::ORGANIZATION, self::CLAIM_A, $occurredAt, $occurredAt]);
    }

    private function messageStatus(string $id): string
    {
        return (string) $this->connection->fetchOne(
            'SELECT status FROM messaging.outbox_messages WHERE id = ?',
            [$id],
        );
    }
}
