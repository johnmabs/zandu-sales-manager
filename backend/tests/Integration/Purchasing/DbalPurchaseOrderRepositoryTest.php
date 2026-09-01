<?php

declare(strict_types=1);

namespace Zandu\Tests\Integration\Purchasing;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zandu\Modules\Purchasing\Domain\PurchaseOrder\PurchaseOrder;
use Zandu\Modules\Purchasing\Domain\PurchaseOrder\PurchaseOrderLine;
use Zandu\Modules\Purchasing\Domain\PurchaseOrder\PurchaseOrderNumber;
use Zandu\Modules\Purchasing\Domain\PurchaseOrder\PurchaseOrderRepository;
use Zandu\Modules\Purchasing\Infrastructure\Persistence\DbalPurchaseOrderRepository;
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\Platform\Persistence\DoctrineTenantTransaction;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\ProductPackagingId;
use Zandu\SharedKernel\Identity\PurchaseOrderId;
use Zandu\SharedKernel\Identity\PurchaseOrderLineId;
use Zandu\SharedKernel\Identity\StoreId;
use Zandu\SharedKernel\Identity\SupplierId;
use Zandu\SharedKernel\Money\Currency;
use Zandu\SharedKernel\Money\Money;
use Zandu\SharedKernel\Quantity\Quantity;

final class DbalPurchaseOrderRepositoryTest extends KernelTestCase
{
    private const ORGANIZATION = '0198da10-0000-7000-8000-000000000001';
    private const STORE = '0198da10-0000-7000-8000-000000000002';
    private const SUPPLIER = '0198da10-0000-7000-8000-000000000003';
    private const UNIT = '0198da10-0000-7000-8000-000000000004';
    private const PRODUCT = '0198da10-0000-7000-8000-000000000005';
    private const PACKAGING = '0198da10-0000-7000-8000-000000000006';
    private const ORDER = '0198da10-0000-7000-8000-000000000007';
    private const LINE = '0198da10-0000-7000-8000-000000000008';
    private const ACTOR = '0198da10-0000-7000-8000-000000000009';

    private Connection $db;
    private PurchaseOrderRepository $repository;
    private DoctrineTenantTransaction $transactions;
    private SymfonyUuidFactory $ids;
    private BrickDecimalFactory $decimals;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->db = self::getContainer()->get(Connection::class);
        $this->repository = new DbalPurchaseOrderRepository($this->db, new SymfonyUuidFactory(), new BrickDecimalFactory());
        $this->transactions = new DoctrineTenantTransaction($this->db, 'zandu_runtime');
        $this->ids = new SymfonyUuidFactory();
        $this->decimals = new BrickDecimalFactory();
        $this->cleanup();
        $this->fixtures();
    }

    protected function tearDown(): void
    {
        $this->cleanup();
        parent::tearDown();
    }

    public function testPurchaseOrderAndLinesRoundTripWithinTenant(): void
    {
        $organizationId = OrganizationId::fromString(self::ORGANIZATION, $this->ids);
        $order = PurchaseOrder::create(
            PurchaseOrderId::fromString(self::ORDER, $this->ids),
            $organizationId,
            StoreId::fromString(self::STORE, $this->ids),
            SupplierId::fromString(self::SUPPLIER, $this->ids),
            PurchaseOrderNumber::fromString('PO-001'),
            Currency::fromCode('XAF'),
            $this->money('0'),
            ActorId::fromString(self::ACTOR, $this->ids),
            new DateTimeImmutable('2026-08-28T12:00:00Z'),
        );
        $order->addLine(new PurchaseOrderLine(
            PurchaseOrderLineId::fromString(self::LINE, $this->ids),
            $order->id(),
            ProductId::fromString(self::PRODUCT, $this->ids),
            ProductPackagingId::fromString(self::PACKAGING, $this->ids),
            $this->quantity('5'),
            $this->quantity('12'),
            $this->quantity('60'),
            $this->money('120'),
            $this->money('10'),
            $this->quantity('0'),
        ));

        $restored = $this->transactions->transactional($organizationId, function () use ($order): PurchaseOrder {
            $this->repository->save($order);

            return $this->repository->get($order->organizationId(), $order->id());
        });

        self::assertSame('PO-001', $restored->number()->value());
        self::assertSame('600.000000', $restored->expectedTotal()->amount()->toString());
        self::assertSame('60.000000000000', $restored->lines()[0]->orderedBaseQuantity()->toString());
        self::assertSame(2, $restored->version());
        self::assertTrue($this->transactions->transactional($organizationId, fn(): bool => $this->repository->hasOpenForStore($organizationId, StoreId::fromString(self::STORE, $this->ids))));

        $received = $this->transactions->transactional($organizationId, function () use ($restored): PurchaseOrder {
            $restored->confirm(ActorId::fromString(self::ACTOR, $this->ids), new DateTimeImmutable('2026-08-28T13:00:00Z'));
            $this->repository->save($restored);
            $restored->recordReceipt($restored->lines()[0]->id(), $this->quantity('5'), ActorId::fromString(self::ACTOR, $this->ids), new DateTimeImmutable('2026-08-28T14:00:00Z'));
            $this->repository->save($restored);

            return $this->repository->get($restored->organizationId(), $restored->id());
        });

        self::assertSame('PARTIALLY_RECEIVED', $received->status()->value);
        self::assertSame('5.000000000000', $received->lines()[0]->receivedQuantity()->toString());
        self::assertSame(4, $received->version());

        $overReceived = $this->transactions->transactional($organizationId, function () use ($received): PurchaseOrder {
            $received->recordReceipt($received->lines()[0]->id(), $this->quantity('60'), ActorId::fromString(self::ACTOR, $this->ids), new DateTimeImmutable('2026-08-28T15:00:00Z'), true);
            $this->repository->save($received);

            return $this->repository->get($received->organizationId(), $received->id());
        });

        self::assertSame('FULLY_RECEIVED', $overReceived->status()->value);
        self::assertSame('65.000000000000', $overReceived->lines()[0]->receivedQuantity()->toString());
        self::assertSame(5, $overReceived->version());
        self::assertFalse($this->transactions->transactional($organizationId, fn(): bool => $this->repository->hasOpenForStore($organizationId, StoreId::fromString(self::STORE, $this->ids))));
    }

    private function fixtures(): void
    {
        $this->db->executeStatement("INSERT INTO organization.organizations (id,name,status,country_code,default_currency,default_time_zone,default_locale,created_by,created_at,updated_by,updated_at,version) VALUES (?, 'PO tenant','ACTIVE','CG','XAF','Africa/Brazzaville','fr_CG',?,NOW(),?,NOW(),1)", [self::ORGANIZATION, self::ACTOR, self::ACTOR]);
        $this->db->executeStatement("INSERT INTO organization.stores (id,organization_id,code,name,status,time_zone,currency,locale,created_by,created_at,updated_by,updated_at,version) VALUES (?,?,'MAIN','Main','ACTIVE','Africa/Brazzaville','XAF','fr_CG',?,NOW(),?,NOW(),1)", [self::STORE, self::ORGANIZATION, self::ACTOR, self::ACTOR]);
        $this->db->executeStatement("INSERT INTO purchasing.supplier (id,organization_id,name,status,created_at,created_by,version) VALUES (?,?,'Supplier','ACTIVE',NOW(),?,1)", [self::SUPPLIER, self::ORGANIZATION, self::ACTOR]);
        $this->db->executeStatement("INSERT INTO catalog.units_of_measure (id,organization_id,code,name,dimension,precision,rounding_mode,status,version) VALUES (?,?,'EA','Article','COUNT',0,'HalfUp','ACTIVE',1)", [self::UNIT, self::ORGANIZATION]);
        $this->db->executeStatement("INSERT INTO catalog.products (id,organization_id,product_code,name,status,type,base_unit_id,inventory_tracked,created_at,created_by,activated_at,activated_by,version) VALUES (?,?,'SKU','Product','ACTIVE','PHYSICAL',?,TRUE,NOW(),?,NOW(),?,1)", [self::PRODUCT, self::ORGANIZATION, self::UNIT, self::ACTOR, self::ACTOR]);
        $this->db->executeStatement("INSERT INTO catalog.product_packagings (id,organization_id,product_id,base,code,name,unit_id,conversion_factor,precision,minimum_quantity,quantity_increment,allowed_for_sale,allowed_for_purchase,status,created_at,created_by,version) VALUES (?,?,?,FALSE,'CASE','Carton',?,12,0,1,1,TRUE,TRUE,'ACTIVE',NOW(),?,1)", [self::PACKAGING, self::ORGANIZATION, self::PRODUCT, self::UNIT, self::ACTOR]);
    }

    private function cleanup(): void
    {
        $this->db->executeStatement("UPDATE purchasing.purchase_order SET status = 'DRAFT' WHERE organization_id = ?", [self::ORGANIZATION]);
        foreach (['purchasing.purchase_order_line', 'purchasing.purchase_order', 'purchasing.supplier', 'catalog.product_packagings', 'catalog.products', 'catalog.units_of_measure', 'organization.stores'] as $table) {
            $this->db->executeStatement(sprintf('DELETE FROM %s WHERE organization_id = ?', $table), [self::ORGANIZATION]);
        }
        $this->db->executeStatement('DELETE FROM organization.organizations WHERE id = ?', [self::ORGANIZATION]);
    }

    private function money(string $value): Money
    {
        return Money::fromString($value, Currency::fromCode('XAF'), $this->decimals);
    }
    private function quantity(string $value): Quantity
    {
        return Quantity::fromString($value, $this->decimals);
    }
}
