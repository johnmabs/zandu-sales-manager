<?php

declare(strict_types=1);

namespace Zandu\Tests\Integration\Messaging;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Zandu\Platform\Identity\SymfonyUuidFactory;
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

    private function beginTenantTransaction(Connection $connection): void
    {
        $connection->beginTransaction();
        $connection->executeStatement('SET LOCAL ROLE zandu_runtime');
        $connection->fetchOne(
            "SELECT set_config('app.organization_id', ?, true)",
            [self::ORGANIZATION],
        );
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
