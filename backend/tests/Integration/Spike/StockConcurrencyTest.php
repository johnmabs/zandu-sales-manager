<?php

declare(strict_types=1);

namespace Zandu\Tests\Integration\Spike;

use Zandu\Tests\Integration\PostgresTestCase;

final class StockConcurrencyTest extends PostgresTestCase
{
    private const STOCK_ID = '0198c72c-1111-7111-8111-111111111111';

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetStock();
    }

    public function testOptimisticLockingRejectsTheSecondStaleConsumption(): void
    {
        $transactionA = $this->connection;
        $transactionB = $this->secondConnection();
        $snapshotA = $transactionA->fetchAssociative('SELECT quantity, version FROM architecture_spike.stock WHERE id = ?', [self::STOCK_ID]);
        $snapshotB = $transactionB->fetchAssociative('SELECT quantity, version FROM architecture_spike.stock WHERE id = ?', [self::STOCK_ID]);
        self::assertIsArray($snapshotA);
        self::assertIsArray($snapshotB);

        $updatedA = $transactionA->executeStatement(
            'UPDATE architecture_spike.stock SET quantity = ?, version = version + 1 WHERE id = ? AND version = ?',
            ['1', self::STOCK_ID, $snapshotA['version']],
        );
        $updatedB = $transactionB->executeStatement(
            'UPDATE architecture_spike.stock SET quantity = ?, version = version + 1 WHERE id = ? AND version = ?',
            ['2', self::STOCK_ID, $snapshotB['version']],
        );

        self::assertSame(1, $updatedA);
        self::assertSame(0, $updatedB);
        self::assertSame('1.000000000000', $this->quantity());
        $transactionB->close();
    }

    public function testConditionalUpdatePreventsNegativeStockWithoutLoadingASnapshot(): void
    {
        $transactionA = $this->connection;
        $transactionB = $this->secondConnection();

        $updatedA = $transactionA->executeStatement(
            'UPDATE architecture_spike.stock SET quantity = quantity - 4, version = version + 1 WHERE id = ? AND quantity >= 4',
            [self::STOCK_ID],
        );
        $updatedB = $transactionB->executeStatement(
            'UPDATE architecture_spike.stock SET quantity = quantity - 3, version = version + 1 WHERE id = ? AND quantity >= 3',
            [self::STOCK_ID],
        );

        self::assertSame(1, $updatedA);
        self::assertSame(0, $updatedB);
        self::assertSame('1.000000000000', $this->quantity());
        $transactionB->close();
    }

    private function resetStock(): void
    {
        $this->connection->executeStatement('TRUNCATE architecture_spike.stock');
        $this->connection->insert('architecture_spike.stock', [
            'id' => self::STOCK_ID,
            'quantity' => '5',
            'version' => 0,
        ]);
    }

    private function quantity(): string
    {
        return (string) $this->connection->fetchOne(
            'SELECT quantity FROM architecture_spike.stock WHERE id = ?',
            [self::STOCK_ID],
        );
    }
}
