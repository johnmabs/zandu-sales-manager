<?php

declare(strict_types=1);

namespace Zandu\Tests\Integration\Spike;

use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Zandu\Tests\Integration\PostgresTestCase;

final class CompleteSaleTransactionTest extends PostgresTestCase
{
    private const SALE_ID = '0198c72a-1111-7111-8111-111111111111';
    private const STOCK_ID = '0198c72a-2222-7222-8222-222222222222';
    private const SESSION_ID = '0198c72a-3333-7333-8333-333333333333';

    protected function setUp(): void
    {
        parent::setUp();
        $this->connection->executeStatement('TRUNCATE architecture_spike.sale, architecture_spike.stock, architecture_spike.stock_movement, architecture_spike.cash_session, architecture_spike.cash_movement, architecture_spike.outbox_message');
        $this->connection->insert('architecture_spike.stock', [
            'id' => self::STOCK_ID,
            'quantity' => '5.000000000000',
        ]);
        $this->connection->insert('architecture_spike.cash_session', [
            'id' => self::SESSION_ID,
            'balance' => '0.000000000000',
        ]);
    }

    #[DataProvider('failurePhases')]
    public function testFailureBeforeCommitLeavesZeroPartialBusinessEffect(string $phase): void
    {
        try {
            $this->completeSale($phase);
            self::fail('The injected failure should abort the transaction.');
        } catch (RuntimeException $exception) {
            self::assertSame('Injected ' . $phase . ' failure.', $exception->getMessage());
        }

        self::assertSame(0, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM architecture_spike.sale'));
        self::assertSame(0, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM architecture_spike.stock_movement'));
        self::assertSame(0, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM architecture_spike.cash_movement'));
        self::assertSame(0, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM architecture_spike.outbox_message'));
        self::assertSame('5.000000000000', $this->connection->fetchOne('SELECT quantity FROM architecture_spike.stock WHERE id = ?', [self::STOCK_ID]));
        self::assertSame('0.000000000000', $this->connection->fetchOne('SELECT balance FROM architecture_spike.cash_session WHERE id = ?', [self::SESSION_ID]));
    }

    public function testSuccessfulCompletionCommitsAllEffectsTogether(): void
    {
        $this->completeSale(null);

        self::assertSame(1, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM architecture_spike.sale'));
        self::assertSame('1.000000000000', $this->connection->fetchOne('SELECT quantity FROM architecture_spike.stock WHERE id = ?', [self::STOCK_ID]));
        self::assertSame('100.000000000000', $this->connection->fetchOne('SELECT balance FROM architecture_spike.cash_session WHERE id = ?', [self::SESSION_ID]));
        self::assertSame(1, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM architecture_spike.stock_movement'));
        self::assertSame(1, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM architecture_spike.cash_movement'));
        self::assertSame(1, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM architecture_spike.outbox_message'));
    }

    /** @return iterable<string,array{string}> */
    public static function failurePhases(): iterable
    {
        yield 'inventory' => ['inventory'];
        yield 'cash' => ['cash'];
        yield 'outbox' => ['outbox'];
    }

    private function completeSale(?string $failurePhase): void
    {
        $this->connection->transactional(function () use ($failurePhase): void {
            $this->connection->insert('architecture_spike.sale', ['id' => self::SALE_ID, 'status' => 'COMPLETED']);
            $this->connection->executeStatement('UPDATE architecture_spike.stock SET quantity = quantity - 4 WHERE id = ?', [self::STOCK_ID]);
            $this->connection->insert('architecture_spike.stock_movement', ['id' => '0198c72a-4444-7444-8444-444444444444', 'stock_id' => self::STOCK_ID, 'quantity' => '-4']);
            $this->failAt('inventory', $failurePhase);
            $this->connection->executeStatement('UPDATE architecture_spike.cash_session SET balance = balance + 100 WHERE id = ?', [self::SESSION_ID]);
            $this->connection->insert('architecture_spike.cash_movement', ['id' => '0198c72a-5555-7555-8555-555555555555', 'session_id' => self::SESSION_ID, 'amount' => '100']);
            $this->failAt('cash', $failurePhase);
            $this->connection->insert('architecture_spike.outbox_message', ['id' => '0198c72a-6666-7666-8666-666666666666', 'payload' => '{"type":"SaleCompleted"}']);
            $this->failAt('outbox', $failurePhase);
        });
    }

    private function failAt(string $phase, ?string $failurePhase): void
    {
        if ($phase === $failurePhase) {
            throw new RuntimeException('Injected ' . $phase . ' failure.');
        }
    }
}
