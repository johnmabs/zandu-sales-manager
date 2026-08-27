<?php

declare(strict_types=1);

namespace Zandu\Tests\Integration\Tenancy;

use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\Platform\Persistence\DoctrineTenantTransaction;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\Tests\Integration\PostgresTestCase;

final class LotFourTenantIsolationTest extends PostgresTestCase
{
    private const A = '0198faa1-1111-7111-8111-111111111111';
    private const B = '0198faa2-1111-7111-8111-111111111111';
    private const STORE_A = '0198faa3-1111-7111-8111-111111111111';
    private const STORE_B = '0198faa4-1111-7111-8111-111111111111';
    private const SALE_A = '0198faa5-1111-7111-8111-111111111111';
    private const SALE_B = '0198faa6-1111-7111-8111-111111111111';
    private const LINE_A = '0198faa7-1111-7111-8111-111111111111';
    private const LINE_B = '0198faa8-1111-7111-8111-111111111111';
    private const PAYMENT_A = '0198faa9-1111-7111-8111-111111111111';
    private const PAYMENT_B = '0198faaa-1111-7111-8111-111111111111';
    private const ACTOR = '0198faab-1111-7111-8111-111111111111';

    protected function setUp(): void
    {
        parent::setUp();
        $this->cleanup();
        foreach ([[self::A, self::STORE_A, self::SALE_A, self::LINE_A, self::PAYMENT_A], [self::B, self::STORE_B, self::SALE_B, self::LINE_B, self::PAYMENT_B]] as [$organization, $store, $sale, $line, $payment]) {
            $this->connection->executeStatement("INSERT INTO organization.organizations (id,name,status,country_code,default_currency,default_time_zone,default_locale,created_by,created_at,updated_by,updated_at,version) VALUES (?, ?, 'ACTIVE','CG','XAF','Africa/Brazzaville','fr_CG',?,NOW(),?,NOW(),1)", [$organization, 'Lot 4 ' . $organization, self::ACTOR, self::ACTOR]);
            $this->connection->executeStatement("INSERT INTO organization.stores (id,organization_id,code,name,status,time_zone,currency,locale,created_by,created_at,updated_by,updated_at,version) VALUES (?,?,'MAIN','Main','ACTIVE','Africa/Brazzaville','XAF','fr_CG',?,NOW(),?,NOW(),1)", [$store, $organization, self::ACTOR, self::ACTOR]);
            $this->connection->executeStatement("INSERT INTO sales.sale (id,organization_id,store_id,status,currency,subtotal,discount_total,tax_total,total,created_by,created_at,version) VALUES (?,?,?,'DRAFT','XAF',100,0,0,100,?,NOW(),1)", [$sale, $organization, $store, self::ACTOR]);
            $this->connection->executeStatement("INSERT INTO sales.sale_line (id,organization_id,sale_id,line_number,product_id,product_packaging_id,packaging_code_snapshot,unit_id_snapshot,entered_quantity,conversion_factor_snapshot,base_quantity,unit_price,discount_amount,taxable_amount,tax_amount,subtotal,total,source_versions) VALUES (?,?,?,1,?,?, 'UNIT',?,1,1,1,100,0,100,0,100,100,'{}')", [$line, $organization, $sale, self::ACTOR, self::ACTOR, self::ACTOR]);
            $this->connection->executeStatement("INSERT INTO payments.payment (id,organization_id,purpose,target_reference,method,status,amount,currency,created_by,created_at,confirmed_at,version) VALUES (?,?,'SALE',?,'CASH','CONFIRMED',100,'XAF',?,NOW(),NOW(),2)", [$payment, $organization, $sale, self::ACTOR]);
        }
    }

    protected function tearDown(): void
    {
        $this->cleanup();
        parent::tearDown();
    }

    public function testRuntimeTenantCanOnlyReadItsSalesLinesAndPayments(): void
    {
        $transaction = new DoctrineTenantTransaction($this->connection, 'zandu_runtime');
        $visible = $transaction->transactional($this->organization(self::A), fn(): array => [
            'sales' => $this->connection->fetchFirstColumn('SELECT id FROM sales.sale ORDER BY id'),
            'lines' => $this->connection->fetchFirstColumn('SELECT id FROM sales.sale_line ORDER BY id'),
            'payments' => $this->connection->fetchFirstColumn('SELECT id FROM payments.payment ORDER BY id'),
        ]);

        self::assertSame([self::SALE_A], $visible['sales']);
        self::assertSame([self::LINE_A], $visible['lines']);
        self::assertSame([self::PAYMENT_A], $visible['payments']);
    }

    private function organization(string $id): OrganizationId
    {
        return OrganizationId::fromString($id, new SymfonyUuidFactory());
    }

    private function cleanup(): void
    {
        $organizations = [self::A, self::B];
        $this->connection->executeStatement('DELETE FROM payments.payment WHERE organization_id IN (?, ?)', $organizations);
        $this->connection->executeStatement('DELETE FROM sales.sale_completion_keys WHERE organization_id IN (?, ?)', $organizations);
        $this->connection->executeStatement('DELETE FROM sales.sale_line WHERE organization_id IN (?, ?)', $organizations);
        $this->connection->executeStatement('DELETE FROM sales.sale WHERE organization_id IN (?, ?)', $organizations);
        $this->connection->executeStatement('DELETE FROM organization.stores WHERE organization_id IN (?, ?)', $organizations);
        $this->connection->executeStatement('DELETE FROM organization.organizations WHERE id IN (?, ?)', $organizations);
    }
}
