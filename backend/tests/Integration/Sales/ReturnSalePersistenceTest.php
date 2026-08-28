<?php

declare(strict_types=1);

namespace Zandu\Tests\Integration\Sales;

use DateTimeImmutable;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception\DriverException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;
use Zandu\Modules\Sales\Application\ReturnAmountCalculator;
use Zandu\Modules\Sales\Domain\{ReturnSale, ReturnSaleLine, ReturnSaleRepository, ReturnSaleStatus, SaleLineCostSnapshotRepository, SaleRepository};
use Zandu\Modules\Sales\Infrastructure\Persistence\{DbalReturnSaleRepository, DbalSaleLineCostSnapshotRepository, DbalSaleRepository};
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\Platform\Persistence\DoctrineTenantTransaction;
use Zandu\SharedKernel\Context\{ActorContext, ActorType};
use Zandu\SharedKernel\Identity\{ActorId, OrganizationId, ReturnSaleId, ReturnSaleLineId, SaleId};
use Zandu\SharedKernel\Messaging\CorrelationId;
use Zandu\SharedKernel\Quantity\Quantity;

final class ReturnSalePersistenceTest extends KernelTestCase
{
    private const A = '019a3200-0000-7000-8000-000000000001';
    private const B = '019a3200-0000-7000-8000-000000000002';
    private const ACTOR = '019a3200-0000-7000-8000-000000000003';

    private \Doctrine\DBAL\Connection $connection;
    private DoctrineTenantTransaction $transaction;
    private ReturnSaleRepository $returns;
    private SaleRepository $sales;
    private SaleLineCostSnapshotRepository $costs;
    private SymfonyUuidFactory $uuids;
    private BrickDecimalFactory $decimals;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $this->connection = $container->get(\Doctrine\DBAL\Connection::class);
        $this->transaction = new DoctrineTenantTransaction($this->connection, 'zandu_runtime');
        $this->uuids = new SymfonyUuidFactory();
        $this->decimals = new BrickDecimalFactory();
        $this->sales = new DbalSaleRepository($this->connection, $this->uuids, $this->decimals);
        $this->costs = new DbalSaleLineCostSnapshotRepository($this->connection, $this->uuids, $this->decimals);
        $this->returns = new DbalReturnSaleRepository($this->connection, $this->sales, $this->costs, $this->uuids, $this->decimals);
        $this->cleanup();
        $this->fixture(self::A, 'a');
        $this->fixture(self::B, 'b');
    }

    protected function tearDown(): void
    {
        $this->cleanup();
        parent::tearDown();
    }

    public function testReturnSaleRoundTripsWithOriginalCommercialAndCostSnapshots(): void
    {
        $organization = $this->organization(self::A);
        $returnId = $this->returnId('a');
        $return = $this->transaction->transactional($organization, function () use ($organization, $returnId): ReturnSale {
            $sale = $this->sales->get($organization, $this->saleId('a'));
            $cost = $this->costs->findBySaleLine($organization, $sale->lines()[0]->id());
            self::assertNotNull($cost);
            $return = ReturnSale::create(
                $returnId,
                $organization,
                $sale->storeId(),
                $sale->id(),
                'Customer return',
                $this->actor($organization),
                new DateTimeImmutable('2026-08-28T12:00:00+01:00'),
            );
            $return->addLine(new ReturnSaleLine(
                $this->returnLineId('a'),
                $sale->lines()[0],
                $cost,
                $this->quantity('1'),
                true,
                'Sealed item',
            ));
            $this->returns->save($return);

            return $return;
        });
        self::assertSame(2, $return->version());

        $restored = $this->transaction->transactional($organization, fn(): ReturnSale => $this->returns->get($organization, $returnId));
        self::assertSame(ReturnSaleStatus::Draft, $restored->status());
        self::assertSame('Customer return', $restored->reason());
        self::assertSame('SKU-a', $restored->lines()[0]->originalLine()->productCodeSnapshot());
        self::assertSame('6.000000000000', $restored->lines()[0]->baseReturnedQuantity()->toString());
        self::assertSame('400.000000000000', $restored->lines()[0]->originalCostSnapshot()?->unitCost()->amount()->toString());
        self::assertTrue($restored->lines()[0]->restock());

        $this->transaction->transactional($organization, function () use ($organization, $returnId): void {
            $locked = $this->returns->getForUpdate($organization, $returnId);
            $line = $locked->lines()[0];
            $zero = $line->baseReturnedQuantity()->subtract($line->baseReturnedQuantity());
            $amounts = (new ReturnAmountCalculator())->calculate($line->originalLine(), $zero, $line->baseReturnedQuantity());
            $locked->complete(
                $this->actor($organization),
                new DateTimeImmutable('2026-08-28T13:00:00+01:00'),
                '2026-08-28',
                [$line->id()->toString() => $amounts],
            );
            $this->returns->save($locked);
        });
        $completed = $this->transaction->transactional($organization, fn(): ReturnSale => $this->returns->get($organization, $returnId));
        self::assertSame(ReturnSaleStatus::Completed, $completed->status());
        self::assertSame('2026-08-28', $completed->businessDate());
        self::assertSame('9000.000000000000', $completed->lines()[0]->amounts()?->total()->amount()->toString());
        self::assertFalse((bool) $this->connection->fetchOne("SELECT has_table_privilege('zandu_runtime', 'sales.return_sale_line_amount', 'UPDATE')"));
        self::assertFalse((bool) $this->connection->fetchOne("SELECT has_table_privilege('zandu_runtime', 'sales.return_sale_line_amount', 'DELETE')"));
        self::assertCount(1, $this->transaction->transactional($organization, fn(): array => $this->returns->findBySale($organization, $this->saleId('a'))));
    }

    public function testRuntimeTenantCannotReadAnotherTenantReturns(): void
    {
        $organizationB = $this->organization(self::B);
        $this->transaction->transactional($organizationB, function () use ($organizationB): void {
            $sale = $this->sales->get($organizationB, $this->saleId('b'));
            $return = ReturnSale::create($this->returnId('b'), $organizationB, $sale->storeId(), $sale->id(), null, $this->actor($organizationB), new DateTimeImmutable());
            $return->addLine(new ReturnSaleLine($this->returnLineId('b'), $sale->lines()[0], null, $this->quantity('1'), false, null));
            $this->returns->save($return);
        });

        $visible = $this->transaction->transactional(
            $this->organization(self::A),
            fn(): array => $this->returns->findBySale($organizationB, $this->saleId('b')),
        );
        self::assertSame([], $visible);
    }

    public function testRuntimeRoleCannotRewriteAnOriginalReturnLine(): void
    {
        $organization = $this->organization(self::A);
        $this->transaction->transactional($organization, function () use ($organization): void {
            $sale = $this->sales->get($organization, $this->saleId('a'));
            $return = ReturnSale::create($this->returnId('a'), $organization, $sale->storeId(), $sale->id(), null, $this->actor($organization), new DateTimeImmutable());
            $return->addLine(new ReturnSaleLine($this->returnLineId('a'), $sale->lines()[0], null, $this->quantity('1'), false, null));
            $this->returns->save($return);
        });

        $this->expectException(DriverException::class);
        $this->transaction->transactional(
            $organization,
            fn() => $this->connection->executeStatement('UPDATE sales.return_sale_line SET returned_quantity = 2 WHERE organization_id = ?', [self::A]),
        );
    }

    public function testSaleLockSerializesReturnsAndProtectsTheCumulativeQuantity(): void
    {
        $organization = self::A;
        $sale = $this->saleId('a')->toString();
        $line = $this->id('a', 5);
        $returnA = $this->returnId('a')->toString();
        $returnB = '019a3218-0000-7000-8000-000000000018';
        $returnLineA = $this->returnLineId('a')->toString();
        $returnLineB = '019a3219-0000-7000-8000-000000000019';
        $store = $this->id('a', 1);
        $product = $this->id('a', 3);

        foreach ([[$returnA, $returnLineA], [$returnB, $returnLineB]] as [$returnId, $returnLineId]) {
            $this->connection->executeStatement("INSERT INTO sales.return_sale (id,organization_id,store_id,sale_id,status,created_by,created_at,version) VALUES (?,?,?,?,'DRAFT',?,NOW(),2)", [$returnId, $organization, $store, $sale, self::ACTOR]);
            $this->connection->executeStatement('INSERT INTO sales.return_sale_line (id,organization_id,return_sale_id,sale_id,line_number,sale_line_id,product_id,returned_quantity,base_returned_quantity,restock) VALUES (?,?,?,?,1,?,?,8,8,FALSE)', [$returnLineId, $organization, $returnId, $sale, $line, $product]);
        }

        $second = DriverManager::getConnection($this->connection->getParams());
        $this->connection->beginTransaction();
        $this->tenantContext($this->connection, $organization);
        self::assertSame($sale, $this->connection->fetchOne('SELECT id FROM sales.sale WHERE id = ? FOR UPDATE', [$sale]));

        $second->beginTransaction();
        $this->tenantContext($second, $organization);
        $second->executeStatement("SET LOCAL lock_timeout = '100ms'");
        $blocked = false;
        try {
            $second->fetchOne('SELECT id FROM sales.sale WHERE id = ? FOR UPDATE', [$sale]);
        } catch (Throwable) {
            $blocked = true;
        } finally {
            $second->rollBack();
        }
        self::assertTrue($blocked, 'A concurrent return must wait for the source sale lock.');

        $this->connection->executeStatement("UPDATE sales.return_sale SET status = 'COMPLETED', business_date = CURRENT_DATE, completed_by = ?, completed_at = NOW(), version = 3 WHERE id = ?", [self::ACTOR, $returnA]);
        $this->connection->commit();

        $second->beginTransaction();
        $this->tenantContext($second, $organization);
        self::assertSame($sale, $second->fetchOne('SELECT id FROM sales.sale WHERE id = ? FOR UPDATE', [$sale]));
        $alreadyReturned = (string) $second->fetchOne("SELECT COALESCE(SUM(line.base_returned_quantity), 0) FROM sales.return_sale_line line JOIN sales.return_sale parent ON parent.organization_id = line.organization_id AND parent.id = line.return_sale_id WHERE line.organization_id = ? AND line.sale_line_id = ? AND parent.status = 'COMPLETED'", [$organization, $line]);
        self::assertSame('8.000000000000', $alreadyReturned);
        self::assertGreaterThan(0, $this->quantity($alreadyReturned)->add($this->quantity('8'))->compareTo($this->quantity('12')));
        $second->rollBack();
        $second->close();

        self::assertSame('DRAFT', $this->connection->fetchOne('SELECT status FROM sales.return_sale WHERE id = ?', [$returnB]));
    }

    private function fixture(string $organization, string $suffix): void
    {
        $store = $this->id($suffix, 1);
        $unit = $this->id($suffix, 2);
        $product = $this->id($suffix, 3);
        $sale = $this->saleId($suffix)->toString();
        $line = $this->id($suffix, 5);
        $stock = $this->id($suffix, 6);
        $movement = $this->id($suffix, 7);
        $this->connection->executeStatement("INSERT INTO organization.organizations (id,name,status,country_code,default_currency,default_time_zone,default_locale,created_by,created_at,updated_by,updated_at,version) VALUES (?,?,'ACTIVE','CG','XAF','Africa/Brazzaville','fr_CG',?,NOW(),?,NOW(),1)", [$organization, "Returns $suffix", self::ACTOR, self::ACTOR]);
        $this->connection->executeStatement("INSERT INTO organization.stores (id,organization_id,code,name,status,time_zone,currency,locale,created_by,created_at,updated_by,updated_at,version) VALUES (?,?,?,?,'ACTIVE','Africa/Brazzaville','XAF','fr_CG',?,NOW(),?,NOW(),1)", [$store, $organization, "RET-$suffix", "Store $suffix", self::ACTOR, self::ACTOR]);
        $this->connection->executeStatement("INSERT INTO catalog.units_of_measure (id,organization_id,code,name,dimension,precision,rounding_mode,status,version) VALUES (?,?,?,?,'COUNT',0,'HalfUp','ACTIVE',1)", [$unit, $organization, "EA-$suffix", "Unit $suffix"]);
        $this->connection->executeStatement("INSERT INTO catalog.products (id,organization_id,product_code,name,status,type,base_unit_id,inventory_tracked,created_at,created_by,activated_at,activated_by,version) VALUES (?,?,?,?,'ACTIVE','PHYSICAL',?,TRUE,NOW(),?,NOW(),?,1)", [$product, $organization, "SKU-$suffix", "Product $suffix", $unit, self::ACTOR, self::ACTOR]);
        $this->connection->executeStatement('INSERT INTO inventory.stock (id,organization_id,store_id,product_id,quantity_on_hand,initialized,initialized_at,initialized_by,version) VALUES (?,?,?,?,8,TRUE,NOW(),?,2)', [$stock, $organization, $store, $product, self::ACTOR]);
        $this->connection->executeStatement("INSERT INTO sales.sale (id,organization_id,store_id,status,currency,subtotal,discount_total,tax_total,total,created_by,created_at,completed_by,completed_at,business_date,version) VALUES (?,?,?,'COMPLETED','XAF',18000,0,0,18000,?,NOW(),?,NOW(),CURRENT_DATE,2)", [$sale, $organization, $store, self::ACTOR, self::ACTOR]);
        $this->connection->executeStatement("INSERT INTO sales.sale_line (id,organization_id,sale_id,line_number,product_id,product_packaging_id,product_code_snapshot,product_name_snapshot,packaging_code_snapshot,packaging_name_snapshot,unit_id_snapshot,entered_quantity,conversion_factor_snapshot,base_quantity,unit_price,discount_amount,taxable_amount,tax_amount,subtotal,total,source_versions) VALUES (?,?,?,1,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,CAST(? AS JSONB))", [$line, $organization, $sale, $product, $unit, "SKU-$suffix", "Product $suffix", 'PACK-6', 'Pack of six', $unit, '2', '6', '12', '9000', '0', '18000', '0', '18000', '18000', '{}']);
        $this->connection->executeStatement("INSERT INTO inventory.stock_movement (id,organization_id,store_id,product_id,stock_id,type,quantity,previous_quantity,resulting_quantity,source_type,source_reference_id,performed_by,occurred_at) VALUES (?,?,?,?,?,'SALE',2,10,8,'SALE',?,?,NOW())", [$movement, $organization, $store, $product, $stock, $sale, self::ACTOR]);
        $this->connection->executeStatement('INSERT INTO sales.sale_line_cost_snapshot (organization_id,sale_line_id,stock_id,stock_movement_id,quantity,unit_cost,total_cost,currency,valuation_version,occurred_at) VALUES (?,?,?,?,12,400,4800,\'XAF\',2,NOW())', [$organization, $line, $stock, $movement]);
    }

    private function cleanup(): void
    {
        $organizations = [self::A, self::B];
        $this->connection->executeStatement('DELETE FROM sales.return_sale_line_amount WHERE organization_id IN (?, ?)', $organizations);
        $this->connection->executeStatement('DELETE FROM sales.return_sale_line WHERE organization_id IN (?, ?)', $organizations);
        $this->connection->executeStatement('DELETE FROM sales.return_sale WHERE organization_id IN (?, ?)', $organizations);
        $this->connection->executeStatement('DELETE FROM sales.sale_line_cost_snapshot WHERE organization_id IN (?, ?)', $organizations);
        $this->connection->executeStatement('DELETE FROM inventory.stock_movement WHERE organization_id IN (?, ?)', $organizations);
        $this->connection->executeStatement('DELETE FROM inventory.stock WHERE organization_id IN (?, ?)', $organizations);
        $this->connection->executeStatement('DELETE FROM sales.sale_line WHERE organization_id IN (?, ?)', $organizations);
        $this->connection->executeStatement('DELETE FROM sales.sale WHERE organization_id IN (?, ?)', $organizations);
        $this->connection->executeStatement('DELETE FROM catalog.products WHERE organization_id IN (?, ?)', $organizations);
        $this->connection->executeStatement('DELETE FROM catalog.units_of_measure WHERE organization_id IN (?, ?)', $organizations);
        $this->connection->executeStatement('DELETE FROM organization.stores WHERE organization_id IN (?, ?)', $organizations);
        $this->connection->executeStatement('DELETE FROM organization.organizations WHERE id IN (?, ?)', $organizations);
    }

    private function tenantContext(\Doctrine\DBAL\Connection $connection, string $organization): void
    {
        $connection->executeStatement('SET LOCAL ROLE zandu_runtime');
        $connection->executeStatement("SELECT set_config('app.organization_id', ?, true)", [$organization]);
    }

    private function id(string $suffix, int $part): string
    {
        return sprintf('019a32%s%d-0000-7000-8000-00000000000%d', 'a' === $suffix ? '1' : '2', $part, $part);
    }

    private function organization(string $value): OrganizationId
    {
        return OrganizationId::fromString($value, $this->uuids);
    }

    private function saleId(string $suffix): SaleId
    {
        return SaleId::fromString($this->id($suffix, 4), $this->uuids);
    }

    private function returnId(string $suffix): ReturnSaleId
    {
        return ReturnSaleId::fromString($this->id($suffix, 8), $this->uuids);
    }

    private function returnLineId(string $suffix): ReturnSaleLineId
    {
        return ReturnSaleLineId::fromString($this->id($suffix, 9), $this->uuids);
    }

    private function actor(OrganizationId $organization): ActorContext
    {
        return new ActorContext(
            ActorId::fromString(self::ACTOR, $this->uuids),
            $organization,
            ActorType::User,
            CorrelationId::fromString('019a3200-0000-7000-8000-000000000010', $this->uuids),
            new DateTimeImmutable('2026-08-28T10:00:00Z'),
        );
    }

    private function quantity(string $value): Quantity
    {
        return Quantity::fromString($value, $this->decimals);
    }
}
