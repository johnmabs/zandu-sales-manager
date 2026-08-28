<?php

declare(strict_types=1);

namespace Zandu\Tests\Integration\Sales;

use DateTimeImmutable;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zandu\Modules\Sales\Domain\{SaleLineCostSnapshot, SaleLineCostSnapshotRepository};
use Zandu\Modules\Sales\Infrastructure\Persistence\DbalSaleLineCostSnapshotRepository;
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\Platform\Persistence\DoctrineTenantTransaction;
use Zandu\SharedKernel\Identity\{OrganizationId, SaleId, SaleLineId, StockId, StockMovementId};
use Zandu\SharedKernel\Money\{Currency, Money};
use Zandu\SharedKernel\Quantity\Quantity;

final class SaleLineCostSnapshotPersistenceTest extends KernelTestCase
{
    private const A = '0198fa01-1111-7111-8111-111111111111';
    private const B = '0198fa02-1111-7111-8111-111111111111';
    private const ACTOR = '0198fa03-1111-7111-8111-111111111111';

    private \Doctrine\DBAL\Connection $connection;
    private DoctrineTenantTransaction $transaction;
    private SaleLineCostSnapshotRepository $repository;
    private SymfonyUuidFactory $uuids;
    private BrickDecimalFactory $decimals;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->connection = self::getContainer()->get(\Doctrine\DBAL\Connection::class);
        $this->transaction = new DoctrineTenantTransaction($this->connection, 'zandu_runtime');
        $this->uuids = new SymfonyUuidFactory();
        $this->decimals = new BrickDecimalFactory();
        $this->repository = new DbalSaleLineCostSnapshotRepository($this->connection, $this->uuids, $this->decimals);
        $this->cleanup();
        $this->fixture(self::A, 'a');
        $this->fixture(self::B, 'b');
    }

    protected function tearDown(): void
    {
        $this->cleanup();
        parent::tearDown();
    }

    public function testSnapshotRoundTripsWithExactCostAndCannotBeDuplicated(): void
    {
        $snapshot = $this->snapshot(self::A, 'a');
        $this->transaction->transactional($this->organization(self::A), fn() => $this->repository->append($snapshot));

        $restored = $this->transaction->transactional(
            $this->organization(self::A),
            fn(): ?SaleLineCostSnapshot => $this->repository->findBySaleLine($this->organization(self::A), $this->line('a')),
        );
        self::assertInstanceOf(SaleLineCostSnapshot::class, $restored);
        self::assertSame('2.000000000000', $restored->quantity()->toString());
        self::assertSame('400.000000000000', $restored->unitCost()->amount()->toString());
        self::assertSame('800.000000', $restored->totalCost()->amount()->toString());
        self::assertSame(2, $restored->valuationVersion());

        $this->expectException(UniqueConstraintViolationException::class);
        $this->transaction->transactional($this->organization(self::A), fn() => $this->repository->append($snapshot));
    }

    public function testRuntimeTenantCannotReadAnotherTenantSnapshot(): void
    {
        $this->transaction->transactional($this->organization(self::B), fn() => $this->repository->append($this->snapshot(self::B, 'b')));

        $visible = $this->transaction->transactional($this->organization(self::A), fn(): array => [
            $this->repository->findBySaleLine($this->organization(self::B), $this->line('b')),
            $this->repository->findBySale($this->organization(self::B), $this->sale('b')),
        ]);

        self::assertNull($visible[0]);
        self::assertSame([], $visible[1]);
    }

    public function testRuntimeRoleCannotMutateAnExistingSnapshot(): void
    {
        $this->transaction->transactional($this->organization(self::A), fn() => $this->repository->append($this->snapshot(self::A, 'a')));

        $this->expectException(DriverException::class);
        $this->transaction->transactional(
            $this->organization(self::A),
            fn() => $this->connection->executeStatement('UPDATE sales.sale_line_cost_snapshot SET total_cost = 0 WHERE organization_id = ?', [self::A]),
        );
    }

    private function snapshot(string $organization, string $suffix): SaleLineCostSnapshot
    {
        return SaleLineCostSnapshot::capture(
            $this->organization($organization),
            $this->line($suffix),
            $this->stock($suffix),
            $this->movement($suffix),
            $this->quantity('2'),
            $this->money('400'),
            2,
            new DateTimeImmutable('2026-08-28T08:00:00Z'),
        );
    }

    private function fixture(string $organization, string $suffix): void
    {
        $store = $this->id($suffix, 1);
        $unit = $this->id($suffix, 2);
        $product = $this->id($suffix, 3);
        $stock = $this->stock($suffix)->toString();
        $sale = $this->sale($suffix)->toString();
        $line = $this->line($suffix)->toString();
        $movement = $this->movement($suffix)->toString();
        $this->connection->executeStatement("INSERT INTO organization.organizations (id,name,status,country_code,default_currency,default_time_zone,default_locale,created_by,created_at,updated_by,updated_at,version) VALUES (?,?,'ACTIVE','CG','XAF','Africa/Brazzaville','fr_CG',?,NOW(),?,NOW(),1)", [$organization, "Snapshot $suffix", self::ACTOR, self::ACTOR]);
        $this->connection->executeStatement("INSERT INTO organization.stores (id,organization_id,code,name,status,time_zone,currency,locale,created_by,created_at,updated_by,updated_at,version) VALUES (?,?,?,?,'ACTIVE','Africa/Brazzaville','XAF','fr_CG',?,NOW(),?,NOW(),1)", [$store, $organization, "SNAP-$suffix", "Store $suffix", self::ACTOR, self::ACTOR]);
        $this->connection->executeStatement("INSERT INTO catalog.units_of_measure (id,organization_id,code,name,dimension,precision,rounding_mode,status,version) VALUES (?,?,?,?,'COUNT',0,'HalfUp','ACTIVE',1)", [$unit, $organization, "EA-$suffix", "Unit $suffix"]);
        $this->connection->executeStatement("INSERT INTO catalog.products (id,organization_id,product_code,name,status,type,base_unit_id,inventory_tracked,created_at,created_by,activated_at,activated_by,version) VALUES (?,?,?,?,'ACTIVE','PHYSICAL',?,TRUE,NOW(),?,NOW(),?,1)", [$product, $organization, "COST-$suffix", "Product $suffix", $unit, self::ACTOR, self::ACTOR]);
        $this->connection->executeStatement('INSERT INTO inventory.stock (id,organization_id,store_id,product_id,quantity_on_hand,initialized,initialized_at,initialized_by,version) VALUES (?,?,?,?,8,TRUE,NOW(),?,2)', [$stock, $organization, $store, $product, self::ACTOR]);
        $this->connection->executeStatement("INSERT INTO sales.sale (id,organization_id,store_id,status,currency,subtotal,discount_total,tax_total,total,created_by,created_at,completed_by,completed_at,business_date,version) VALUES (?,?,?,'COMPLETED','XAF',2000,0,0,2000,?,NOW(),?,NOW(),CURRENT_DATE,2)", [$sale, $organization, $store, self::ACTOR, self::ACTOR]);
        $this->connection->executeStatement("INSERT INTO sales.sale_line (id,organization_id,sale_id,line_number,product_id,product_packaging_id,packaging_code_snapshot,unit_id_snapshot,entered_quantity,conversion_factor_snapshot,base_quantity,unit_price,discount_amount,taxable_amount,tax_amount,subtotal,total,source_versions) VALUES (?,?,?,1,?,?, 'EA',?,2,1,2,1000,0,2000,0,2000,2000,'{}')", [$line, $organization, $sale, $product, $unit, $unit]);
        $this->connection->executeStatement("INSERT INTO inventory.stock_movement (id,organization_id,store_id,product_id,stock_id,type,quantity,previous_quantity,resulting_quantity,source_type,source_reference_id,performed_by,occurred_at) VALUES (?,?,?,?,?,'SALE',2,10,8,'SALE',?,?,NOW())", [$movement, $organization, $store, $product, $stock, $sale, self::ACTOR]);
    }

    private function cleanup(): void
    {
        $organizations = [self::A, self::B];
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

    private function id(string $suffix, int $part): string
    {
        $digit = 'a' === $suffix ? '1' : '2';

        return sprintf('0198fb%s%d-1111-7111-8111-111111111111', $digit, $part);
    }

    private function organization(string $id): OrganizationId
    {
        return OrganizationId::fromString($id, $this->uuids);
    }
    private function sale(string $suffix): SaleId
    {
        return SaleId::fromString($this->id($suffix, 4), $this->uuids);
    }
    private function line(string $suffix): SaleLineId
    {
        return SaleLineId::fromString($this->id($suffix, 5), $this->uuids);
    }
    private function stock(string $suffix): StockId
    {
        return StockId::fromString($this->id($suffix, 6), $this->uuids);
    }
    private function movement(string $suffix): StockMovementId
    {
        return StockMovementId::fromString($this->id($suffix, 7), $this->uuids);
    }
    private function quantity(string $value): Quantity
    {
        return Quantity::fromString($value, $this->decimals);
    }
    private function money(string $value): Money
    {
        return Money::fromString($value, Currency::fromCode('XAF'), $this->decimals);
    }
}
