<?php

declare(strict_types=1);

namespace ZanduTests\Integration\Tenancy;

use RuntimeException;
use Zandu\Platform\Persistence\DoctrineTenantTransaction;
use Zandu\Tests\Integration\PostgresTestCase;

/** Covers real Lot 3 rows, beyond the generic RLS catalog checks. */
final class LotThreeTenantIsolationTest extends PostgresTestCase
{
    private const A = '0198e9a1-1111-7111-8111-111111111111';
    private const B = '0198e9a2-1111-7111-8111-111111111111';
    private const STORE_A = '0198e9a3-1111-7111-8111-111111111111';
    private const STORE_B = '0198e9a4-1111-7111-8111-111111111111';
    private const REGISTER_A = '0198e9a5-1111-7111-8111-111111111111';
    private const REGISTER_B = '0198e9a6-1111-7111-8111-111111111111';
    private const SESSION_A = '0198e9a7-1111-7111-8111-111111111111';
    private const ACTOR = '0198d1b2-1dd7-7c6d-9855-25e5e205940c';

    protected function setUp(): void
    {
        parent::setUp();
        $this->cleanup();
        foreach ([self::A, self::B] as $organization) {
            $this->connection->executeStatement(
                "INSERT INTO organization.organizations (id,name,status,country_code,default_currency,default_time_zone,default_locale,created_by,created_at,updated_by,updated_at,version) VALUES (?, ?, 'ACTIVE','CG','XAF','Africa/Brazzaville','fr_CG',?,NOW(),?,NOW(),1)",
                [$organization, $organization === self::A ? 'Lot 3 A' : 'Lot 3 B', self::ACTOR, self::ACTOR],
            );
        }
        $this->store(self::A, self::STORE_A);
        $this->store(self::B, self::STORE_B);
        $this->register(self::A, self::STORE_A, self::REGISTER_A);
        $this->register(self::B, self::STORE_B, self::REGISTER_B);
        $this->connection->executeStatement(
            "INSERT INTO cash_management.cash_session (id,organization_id,store_id,cash_register_id,cashier_id,currency,opening_balance,opened_at,status,version) VALUES (?,?,?,? ,?,'XAF',100,NOW(),'OPEN',1)",
            [self::SESSION_A, self::A, self::STORE_A, self::REGISTER_A, self::ACTOR],
        );
    }

    protected function tearDown(): void
    {
        $this->cleanup();
        parent::tearDown();
    }

    public function testTenantARepositoriesCannotReadTenantBRegisterOrSession(): void
    {
        $transaction = new DoctrineTenantTransaction($this->connection, 'zandu_runtime');
        $rows = $transaction->transactional($this->organization(self::A), fn(): array => [
            'register' => $this->connection->fetchOne('SELECT id FROM cash_management.cash_register WHERE id = ?', [self::REGISTER_B]),
            'session' => $this->connection->fetchOne('SELECT id FROM cash_management.cash_session WHERE id = ?', [self::SESSION_A]),
        ]);

        self::assertFalse($rows['register']);
        self::assertSame(self::SESSION_A, $rows['session']);
    }

    public function testCashLedgerRollbackLeavesNoPartialMovement(): void
    {
        $transaction = new DoctrineTenantTransaction($this->connection, 'zandu_runtime');
        try {
            $transaction->transactional($this->organization(self::A), function (): never {
                $this->connection->executeStatement(
                    "INSERT INTO cash_management.cash_movement (id,organization_id,store_id,cash_session_id,type,amount,currency,source_type,reason,performed_by,occurred_at) VALUES ('0198e9a8-1111-7111-8111-111111111111', ?, ?, ?, 'CASH_IN', 25, 'XAF', 'MANUAL', 'rollback', ?, NOW())",
                    [self::A, self::STORE_A, self::SESSION_A, self::ACTOR],
                );
                throw new RuntimeException('Injected ledger failure.');
            });
        } catch (RuntimeException) {
        }

        self::assertSame(0, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM cash_management.cash_movement WHERE organization_id = ?', [self::A]));
    }

    private function organization(string $id): \Zandu\SharedKernel\Identity\OrganizationId
    {
        return \Zandu\SharedKernel\Identity\OrganizationId::fromString($id, new \Zandu\Platform\Identity\SymfonyUuidFactory());
    }

    private function store(string $organization, string $id): void
    {
        $this->connection->executeStatement("INSERT INTO organization.stores (id,organization_id,code,name,status,time_zone,currency,locale,created_by,created_at,updated_by,updated_at,version) VALUES (?,?,'MAIN','Main','ACTIVE','Africa/Brazzaville','XAF','fr_CG',?,NOW(),?,NOW(),1)", [$id, $organization, self::ACTOR, self::ACTOR]);
    }

    private function register(string $organization, string $store, string $id): void
    {
        $this->connection->executeStatement("INSERT INTO cash_management.cash_register (id,organization_id,store_id,code,name,status,created_at,created_by,version) VALUES (?,?,?,'REG-1','Register','ACTIVE',NOW(),?,1)", [$id, $organization, $store, self::ACTOR]);
    }

    private function cleanup(): void
    {
        $this->connection->executeStatement('DELETE FROM cash_management.cash_movement WHERE organization_id IN (?, ?)', [self::A, self::B]);
        $this->connection->executeStatement('DELETE FROM cash_management.cash_session WHERE organization_id IN (?, ?)', [self::A, self::B]);
        $this->connection->executeStatement('DELETE FROM cash_management.cash_register WHERE organization_id IN (?, ?)', [self::A, self::B]);
        $this->connection->executeStatement('DELETE FROM organization.stores WHERE organization_id IN (?, ?)', [self::A, self::B]);
        $this->connection->executeStatement('DELETE FROM organization.organizations WHERE id IN (?, ?)', [self::A, self::B]);
    }
}
