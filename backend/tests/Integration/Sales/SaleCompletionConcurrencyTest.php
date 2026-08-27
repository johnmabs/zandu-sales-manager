<?php

declare(strict_types=1);

namespace Zandu\Tests\Integration\Sales;

use Throwable;
use Zandu\Tests\Integration\PostgresTestCase;

final class SaleCompletionConcurrencyTest extends PostgresTestCase
{
    private const ORGANIZATION = '0198fc01-1111-7111-8111-111111111111';
    private const STORE = '0198fc02-1111-7111-8111-111111111111';
    private const SALE = '0198fc03-1111-7111-8111-111111111111';
    private const ACTOR = '0198fc04-1111-7111-8111-111111111111';

    protected function setUp(): void
    {
        parent::setUp();
        $this->cleanup();
        $this->connection->executeStatement("INSERT INTO organization.organizations (id,name,status,country_code,default_currency,default_time_zone,default_locale,created_by,created_at,updated_by,updated_at,version) VALUES (?, 'Sale lock', 'ACTIVE','CG','XAF','Africa/Brazzaville','fr_CG',?,NOW(),?,NOW(),1)", [self::ORGANIZATION, self::ACTOR, self::ACTOR]);
        $this->connection->executeStatement("INSERT INTO organization.stores (id,organization_id,code,name,status,time_zone,currency,locale,created_by,created_at,updated_by,updated_at,version) VALUES (?,?,'MAIN','Main','ACTIVE','Africa/Brazzaville','XAF','fr_CG',?,NOW(),?,NOW(),1)", [self::STORE, self::ORGANIZATION, self::ACTOR, self::ACTOR]);
        $this->connection->executeStatement("INSERT INTO sales.sale (id,organization_id,store_id,status,currency,subtotal,discount_total,tax_total,total,created_by,created_at,version) VALUES (?,?,?,'DRAFT','XAF',100,0,0,100,?,NOW(),1)", [self::SALE, self::ORGANIZATION, self::STORE, self::ACTOR]);
    }

    protected function tearDown(): void
    {
        $this->cleanup();
        parent::tearDown();
    }

    public function testSaleRowLockSerializesConcurrentCompletionAttempts(): void
    {
        $second = $this->secondConnection();
        $this->connection->beginTransaction();
        $this->tenantContext($this->connection);
        self::assertSame(self::SALE, $this->connection->fetchOne('SELECT id FROM sales.sale WHERE id = ? FOR UPDATE', [self::SALE]));

        $second->beginTransaction();
        $this->tenantContext($second);
        $second->executeStatement("SET LOCAL lock_timeout = '100ms'");
        $blocked = false;
        try {
            $second->fetchOne('SELECT id FROM sales.sale WHERE id = ? FOR UPDATE', [self::SALE]);
        } catch (Throwable) {
            $blocked = true;
        } finally {
            $second->rollBack();
        }
        self::assertTrue($blocked, 'The second completion attempt must wait for the sale row lock.');
        $this->connection->commit();

        $second->beginTransaction();
        $this->tenantContext($second);
        self::assertSame(self::SALE, $second->fetchOne('SELECT id FROM sales.sale WHERE id = ? FOR UPDATE', [self::SALE]));
        $second->rollBack();
        $second->close();
    }

    private function tenantContext(\Doctrine\DBAL\Connection $connection): void
    {
        $connection->executeStatement('SET LOCAL ROLE zandu_runtime');
        $connection->executeStatement("SELECT set_config('app.organization_id', ?, true)", [self::ORGANIZATION]);
    }

    private function cleanup(): void
    {
        $this->connection->executeStatement('DELETE FROM sales.sale_completion_keys WHERE organization_id = ?', [self::ORGANIZATION]);
        $this->connection->executeStatement('DELETE FROM sales.sale_line WHERE organization_id = ?', [self::ORGANIZATION]);
        $this->connection->executeStatement('DELETE FROM sales.sale WHERE organization_id = ?', [self::ORGANIZATION]);
        $this->connection->executeStatement('DELETE FROM organization.stores WHERE organization_id = ?', [self::ORGANIZATION]);
        $this->connection->executeStatement('DELETE FROM organization.organizations WHERE id = ?', [self::ORGANIZATION]);
    }
}
