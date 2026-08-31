<?php

declare(strict_types=1);

namespace Zandu\Tests\Integration\Inventory;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zandu\Modules\Inventory\Domain\StockTransfer\{StockTransfer, StockTransferLine, StockTransferRepository};
use Zandu\Modules\Inventory\Infrastructure\Persistence\DbalStockTransferRepository;
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\Platform\Persistence\DoctrineTenantTransaction;
use Zandu\SharedKernel\Identity\{ActorId, OrganizationId, ProductId, StockTransferId, StockTransferLineId, StoreId};
use Zandu\SharedKernel\Quantity\Quantity;

final class DbalStockTransferRepositoryTest extends KernelTestCase
{
    private const ORGANIZATION = '0198f95c-0000-7000-8000-000000000001';
    private const SOURCE_STORE = '0198f95c-0000-7000-8000-000000000002';
    private const DESTINATION_STORE = '0198f95c-0000-7000-8000-000000000003';
    private const UNIT = '0198f95c-0000-7000-8000-000000000004';
    private const PRODUCT = '0198f95c-0000-7000-8000-000000000005';
    private const TRANSFER = '0198f95c-0000-7000-8000-000000000006';
    private const LINE = '0198f95c-0000-7000-8000-000000000007';
    private const ACTOR = '0198f95c-0000-7000-8000-000000000008';

    private Connection $db;
    private StockTransferRepository $repository;
    private DoctrineTenantTransaction $transactions;
    private SymfonyUuidFactory $ids;
    private BrickDecimalFactory $decimals;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->db = self::getContainer()->get(Connection::class);
        $this->ids = new SymfonyUuidFactory();
        $this->decimals = new BrickDecimalFactory();
        $this->repository = new DbalStockTransferRepository($this->db, $this->ids, $this->decimals);
        $this->transactions = new DoctrineTenantTransaction($this->db, 'zandu_runtime');
        $this->cleanup();
        $this->fixtures();
    }

    protected function tearDown(): void
    {
        $this->cleanup();
        parent::tearDown();
    }

    public function testShippedAndReceivedQuantitiesRoundTripWithinTenant(): void
    {
        $organizationId = OrganizationId::fromString(self::ORGANIZATION, $this->ids);
        $transfer = StockTransfer::create(
            StockTransferId::fromString(self::TRANSFER, $this->ids),
            $organizationId,
            StoreId::fromString(self::SOURCE_STORE, $this->ids),
            StoreId::fromString(self::DESTINATION_STORE, $this->ids),
            ActorId::fromString(self::ACTOR, $this->ids),
            new DateTimeImmutable('2026-08-31T12:00:00Z'),
        );
        $line = new StockTransferLine(
            StockTransferLineId::fromString(self::LINE, $this->ids),
            $transfer->id(),
            ProductId::fromString(self::PRODUCT, $this->ids),
            $this->quantity('5'),
        );
        $transfer->addLine($line);

        $restored = $this->transactions->transactional($organizationId, function () use ($transfer, $line): StockTransfer {
            $this->repository->save($transfer);
            $transfer->ship(
                ActorId::fromString(self::ACTOR, $this->ids),
                new DateTimeImmutable('2026-08-31T13:00:00Z'),
                [$line->id()->toString() => $this->quantity('4')],
            );
            $this->repository->save($transfer);
            $transfer->receive(
                ActorId::fromString(self::ACTOR, $this->ids),
                new DateTimeImmutable('2026-08-31T14:00:00Z'),
                [$line->id()->toString() => $this->quantity('3')],
            );
            $this->repository->save($transfer);

            return $this->repository->get($transfer->organizationId(), $transfer->id());
        });

        self::assertSame('RECEIVED', $restored->status()->value);
        self::assertSame('4.000000000000', $restored->lines()[0]->shippedQuantity()?->toString());
        self::assertSame('3.000000000000', $restored->lines()[0]->receivedQuantity()?->toString());
        self::assertSame(self::ACTOR, $restored->receivedBy()?->toString());
        self::assertSame(4, $restored->version());
        self::assertSame('1.000000000000', $restored->transitDiscrepancies()[self::LINE]->toString());
    }

    private function fixtures(): void
    {
        $this->db->executeStatement("INSERT INTO organization.organizations (id,name,status,country_code,default_currency,default_time_zone,default_locale,created_by,created_at,updated_by,updated_at,version) VALUES (?, 'Transfer tenant','ACTIVE','CG','XAF','Africa/Brazzaville','fr_CG',?,NOW(),?,NOW(),1)", [self::ORGANIZATION, self::ACTOR, self::ACTOR]);
        $this->db->executeStatement("INSERT INTO organization.stores (id,organization_id,code,name,status,time_zone,currency,locale,created_by,created_at,updated_by,updated_at,version) VALUES (?,?,?,'Store','ACTIVE','Africa/Brazzaville','XAF','fr_CG',?,NOW(),?,NOW(),1)", [self::SOURCE_STORE, self::ORGANIZATION, 'SOURCE', self::ACTOR, self::ACTOR]);
        $this->db->executeStatement("INSERT INTO organization.stores (id,organization_id,code,name,status,time_zone,currency,locale,created_by,created_at,updated_by,updated_at,version) VALUES (?,?,?,'Store','ACTIVE','Africa/Brazzaville','XAF','fr_CG',?,NOW(),?,NOW(),1)", [self::DESTINATION_STORE, self::ORGANIZATION, 'DESTINATION', self::ACTOR, self::ACTOR]);
        $this->db->executeStatement("INSERT INTO catalog.units_of_measure (id,organization_id,code,name,dimension,precision,rounding_mode,status,version) VALUES (?,?,'EA','Article','COUNT',0,'HalfUp','ACTIVE',1)", [self::UNIT, self::ORGANIZATION]);
        $this->db->executeStatement("INSERT INTO catalog.products (id,organization_id,product_code,name,status,type,base_unit_id,inventory_tracked,created_at,created_by,activated_at,activated_by,version) VALUES (?,?,'TRANSFER-SKU','Product','ACTIVE','PHYSICAL',?,TRUE,NOW(),?,NOW(),?,1)", [self::PRODUCT, self::ORGANIZATION, self::UNIT, self::ACTOR, self::ACTOR]);
    }

    private function cleanup(): void
    {
        foreach (['inventory.stock_transfer_line', 'inventory.stock_transfer', 'catalog.products', 'catalog.units_of_measure', 'organization.stores'] as $table) {
            $this->db->executeStatement(sprintf('DELETE FROM %s WHERE organization_id = ?', $table), [self::ORGANIZATION]);
        }
        $this->db->executeStatement('DELETE FROM organization.organizations WHERE id = ?', [self::ORGANIZATION]);
    }

    private function quantity(string $value): Quantity
    {
        return Quantity::fromString($value, $this->decimals);
    }
}
