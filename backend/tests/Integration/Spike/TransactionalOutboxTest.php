<?php

declare(strict_types=1);

namespace Zandu\Tests\Integration\Spike;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Zandu\Tests\Integration\PostgresTestCase;

final class TransactionalOutboxTest extends PostgresTestCase
{
    private const MESSAGE_A = '0198c72b-1111-7111-8111-111111111111';
    private const MESSAGE_B = '0198c72b-2222-7222-8222-222222222222';

    protected function setUp(): void
    {
        parent::setUp();
        $this->connection->executeStatement('TRUNCATE architecture_spike.outbox_message, architecture_spike.processed_message');
        $this->insertMessage(self::MESSAGE_A);
        $this->insertMessage(self::MESSAGE_B);
    }

    public function testSkipLockedMakesMessageClaimingSafeForMultipleWorkers(): void
    {
        $workerA = $this->connection;
        $workerB = $this->secondConnection();
        $workerA->beginTransaction();

        try {
            $messageA = $this->claimOne($workerA);
            $workerB->beginTransaction();
            $messageB = $this->claimOne($workerB);
            $workerB->commit();

            self::assertSame(self::MESSAGE_A, $messageA);
            self::assertSame(self::MESSAGE_B, $messageB);
            self::assertNotSame($messageA, $messageB);
        } finally {
            $workerA->rollBack();
            $workerB->close();
        }

        self::assertSame('PENDING', $this->messageStatus(self::MESSAGE_A));
        self::assertSame('PROCESSING', $this->messageStatus(self::MESSAGE_B));
    }

    public function testWorkerCrashMakesTheRolledBackMessageClaimableAgain(): void
    {
        $this->connection->beginTransaction();
        self::assertSame(self::MESSAGE_A, $this->claimOne($this->connection));
        $this->connection->rollBack();

        $this->connection->beginTransaction();
        try {
            self::assertSame(self::MESSAGE_A, $this->claimOne($this->connection));
            $this->connection->commit();
        } catch (\Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }

    public function testRetriesEventuallyMoveAPoisonMessageToDeadLetter(): void
    {
        for ($attempt = 1; $attempt <= 3; ++$attempt) {
            $status = 3 === $attempt ? 'DEAD_LETTER' : 'PENDING';
            $this->connection->executeStatement(
                'UPDATE architecture_spike.outbox_message SET attempts = attempts + 1, status = ?, available_at = NOW() WHERE id = ?',
                [$status, self::MESSAGE_A],
            );
        }

        $row = $this->connection->fetchAssociative(
            'SELECT status, attempts FROM architecture_spike.outbox_message WHERE id = ?',
            [self::MESSAGE_A],
        );
        self::assertIsArray($row);
        self::assertSame('DEAD_LETTER', $row['status']);
        self::assertSame(3, (int) $row['attempts']);
    }

    public function testAtLeastOnceDeliveryIsMadeIdempotentByTheConsumerLedger(): void
    {
        for ($delivery = 0; $delivery < 2; ++$delivery) {
            $this->connection->executeStatement(
                'INSERT INTO architecture_spike.processed_message (consumer, message_id) VALUES (?, ?) ON CONFLICT DO NOTHING',
                ['inventory_projection', self::MESSAGE_A],
            );
        }

        self::assertSame(1, (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM architecture_spike.processed_message WHERE consumer = ? AND message_id = ?',
            ['inventory_projection', self::MESSAGE_A],
        ));
    }

    private function claimOne(Connection $connection): string
    {
        return (string) $connection->fetchOne(<<<'SQL'
WITH candidate AS (
    SELECT id
    FROM architecture_spike.outbox_message
    WHERE status = 'PENDING' AND available_at <= NOW()
    ORDER BY id
    FOR UPDATE SKIP LOCKED
    LIMIT 1
)
UPDATE architecture_spike.outbox_message message
SET status = 'PROCESSING', claimed_until = NOW() + INTERVAL '30 seconds'
FROM candidate
WHERE message.id = candidate.id
RETURNING message.id
SQL);
    }

    private function insertMessage(string $id): void
    {
        $this->connection->insert('architecture_spike.outbox_message', [
            'id' => $id,
            'payload' => '{"type":"SaleCompleted"}',
            'available_at' => new DateTimeImmutable(),
        ], ['available_at' => 'datetimetz_immutable']);
    }

    private function messageStatus(string $id): string
    {
        return (string) $this->connection->fetchOne(
            'SELECT status FROM architecture_spike.outbox_message WHERE id = ?',
            [$id],
        );
    }
}
