<?php

declare(strict_types=1);

namespace Zandu\Tests\Integration\Purchasing;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Inventory\Application\Contract\InventoryPurchaseReturnShipper;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Modules\Purchasing\Application\ShipPurchaseReturn\ShipPurchaseReturn;
use Zandu\Modules\Purchasing\Application\ShipPurchaseReturn\ShipPurchaseReturnHandler;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\GoodsReceipt;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\GoodsReceiptLine;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\GoodsReceiptNumber;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\GoodsReceiptRepository;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\GoodsReceiptStatus;
use Zandu\Modules\Purchasing\Domain\GoodsReceiptCorrection\GoodsReceiptCorrection;
use Zandu\Modules\Purchasing\Domain\GoodsReceiptCorrection\GoodsReceiptCorrectionLine;
use Zandu\Modules\Purchasing\Domain\GoodsReceiptCorrection\GoodsReceiptCorrectionStatus;
use Zandu\Modules\Purchasing\Domain\PurchaseReturn\{PurchaseReturn, PurchaseReturnLine, PurchaseReturnStatus};
use Zandu\Modules\Purchasing\Infrastructure\Persistence\DbalGoodsReceiptCorrectionRepository;
use Zandu\Modules\Purchasing\Infrastructure\Persistence\DbalGoodsReceiptRepository;
use Zandu\Modules\Purchasing\Infrastructure\Persistence\DbalPurchaseReturnRepository;
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\Platform\Identity\SymfonyUuidV7Generator;
use Zandu\Platform\Persistence\DoctrineTenantTransaction;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\GoodsReceiptCorrectionId;
use Zandu\SharedKernel\Identity\GoodsReceiptId;
use Zandu\SharedKernel\Identity\GoodsReceiptLineId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\ProductPackagingId;
use Zandu\SharedKernel\Identity\{PurchaseReturnId, PurchaseReturnLineId};
use Zandu\SharedKernel\Identity\StoreId;
use Zandu\SharedKernel\Identity\SupplierId;
use Zandu\SharedKernel\Messaging\{OutboxMessage, OutboxRepository};
use Zandu\SharedKernel\Money\Currency;
use Zandu\SharedKernel\Money\Money;
use Zandu\SharedKernel\Quantity\Quantity;
use Zandu\SharedKernel\SecurityAudit\SecurityAuditTrail;
use Zandu\Tests\SharedKernel\Time\FrozenClock;

final class DbalGoodsReceiptRepositoryTest extends KernelTestCase
{
    private const ORGANIZATION = '0198da20-0000-7000-8000-000000000001';
    private const STORE = '0198da20-0000-7000-8000-000000000002';
    private const SUPPLIER = '0198da20-0000-7000-8000-000000000003';
    private const UNIT = '0198da20-0000-7000-8000-000000000004';
    private const PRODUCT = '0198da20-0000-7000-8000-000000000005';
    private const PACKAGING = '0198da20-0000-7000-8000-000000000006';
    private const RECEIPT = '0198da20-0000-7000-8000-000000000007';
    private const LINE = '0198da20-0000-7000-8000-000000000008';
    private const ACTOR = '0198da20-0000-7000-8000-000000000009';
    private const CORRECTION = '0198da20-0000-7000-8000-000000000010';
    private const PURCHASE_RETURN = '0198da20-0000-7000-8000-000000000011';
    private const PURCHASE_RETURN_LINE = '0198da20-0000-7000-8000-000000000012';
    private const STOCK = '0198da20-0000-7000-8000-000000000013';
    private const VALUATION = '0198da20-0000-7000-8000-000000000014';
    private const OTHER_ORGANIZATION = '0198da20-0000-7000-8000-000000000099';

    private Connection $db;
    private GoodsReceiptRepository $repository;
    private DoctrineTenantTransaction $transactions;
    private SymfonyUuidFactory $ids;
    private BrickDecimalFactory $decimals;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->db = self::getContainer()->get(Connection::class);
        $this->repository = new DbalGoodsReceiptRepository($this->db, new SymfonyUuidFactory(), new BrickDecimalFactory());
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

    public function testDirectReceiptAndLinesSurvivePostingWithinTenant(): void
    {
        $organizationId = OrganizationId::fromString(self::ORGANIZATION, $this->ids);
        $receipt = GoodsReceipt::create(
            GoodsReceiptId::fromString(self::RECEIPT, $this->ids),
            $organizationId,
            StoreId::fromString(self::STORE, $this->ids),
            SupplierId::fromString(self::SUPPLIER, $this->ids),
            null,
            GoodsReceiptNumber::fromString('GR-001'),
            'DN-42',
            'Direct delivery',
            ActorId::fromString(self::ACTOR, $this->ids),
            new DateTimeImmutable('2026-08-29T08:00:00Z'),
        );
        $receipt->addLine(new GoodsReceiptLine(
            GoodsReceiptLineId::fromString(self::LINE, $this->ids),
            $receipt->id(),
            ProductId::fromString(self::PRODUCT, $this->ids),
            ProductPackagingId::fromString(self::PACKAGING, $this->ids),
            $this->quantity('5'),
            $this->quantity('12'),
            $this->quantity('60'),
            $this->money('120'),
            $this->money('10'),
            null,
        ));

        $restored = $this->transactions->transactional($organizationId, function () use ($receipt): GoodsReceipt {
            $this->repository->save($receipt);

            return $this->repository->get($receipt->organizationId(), $receipt->id());
        });

        self::assertSame('GR-001', $restored->number()->value());
        self::assertSame('DN-42', $restored->supplierDeliveryNote());
        self::assertSame('60.000000000000', $restored->lines()[0]->receivedBaseQuantity()->toString());
        self::assertSame('120.000000000000', $restored->lines()[0]->actualUnitCost()?->amount()->toString());
        self::assertSame('10.000000000000', $restored->lines()[0]->inventoryUnitCost()->amount()->toString());
        self::assertSame(2, $restored->version());

        $posted = $this->transactions->transactional($organizationId, function () use ($restored): GoodsReceipt {
            $restored->post(ActorId::fromString(self::ACTOR, $this->ids), new DateTimeImmutable('2026-08-29T09:00:00Z'));
            $this->repository->save($restored);

            return $this->repository->get($restored->organizationId(), $restored->id());
        });

        self::assertSame(GoodsReceiptStatus::Posted, $posted->status());
        self::assertSame('2026-08-29T09:00:00+00:00', $posted->postedAt()?->format(DATE_ATOM));
        self::assertCount(1, $posted->lines());
        self::assertSame(3, $posted->version());

        $correctionRepository = new DbalGoodsReceiptCorrectionRepository($this->db, $this->ids, $this->decimals);
        $correction = GoodsReceiptCorrection::create(GoodsReceiptCorrectionId::fromString(self::CORRECTION, $this->ids), $organizationId, $posted->id(), 'Damaged unit found during recount', ActorId::fromString(self::ACTOR, $this->ids), new DateTimeImmutable('2026-08-29T10:00:00Z'));
        $correction->addLine(new GoodsReceiptCorrectionLine($correction->id(), ProductId::fromString(self::PRODUCT, $this->ids), $this->quantity('60'), $this->quantity('60'), $this->quantity('55')));

        $restoredCorrection = $this->transactions->transactional($organizationId, function () use ($correctionRepository, $correction): GoodsReceiptCorrection {
            $correctionRepository->save($correction);
            return $correctionRepository->get($correction->organizationId(), $correction->id());
        });
        self::assertSame('-5.000000000000', $restoredCorrection->lines()[0]->difference()->toString());

        $this->transactions->transactional($organizationId, function () use ($correctionRepository, $restoredCorrection): void {
            $restoredCorrection->post(ActorId::fromString(self::ACTOR, $this->ids), new DateTimeImmutable('2026-08-29T11:00:00Z'));
            $correctionRepository->save($restoredCorrection);
        });
        self::assertSame(GoodsReceiptCorrectionStatus::Posted, $restoredCorrection->status());
        self::assertSame('-5.000000000000', $correctionRepository->postedDifferenceByProduct($organizationId, $posted->id())[self::PRODUCT]->toString());

        $returnRepository = new DbalPurchaseReturnRepository($this->db, $this->ids, $this->decimals);
        $purchaseReturn = PurchaseReturn::create(PurchaseReturnId::fromString(self::PURCHASE_RETURN, $this->ids), $organizationId, StoreId::fromString(self::STORE, $this->ids), SupplierId::fromString(self::SUPPLIER, $this->ids), $posted->id(), null, 'Damaged goods', ActorId::fromString(self::ACTOR, $this->ids), new DateTimeImmutable('2026-08-29T12:00:00Z'));
        $purchaseReturn->addLine(new PurchaseReturnLine(PurchaseReturnLineId::fromString(self::PURCHASE_RETURN_LINE, $this->ids), $purchaseReturn->id(), ProductId::fromString(self::PRODUCT, $this->ids), $this->quantity('2'), GoodsReceiptLineId::fromString(self::LINE, $this->ids)));
        $restoredReturn = $this->transactions->transactional($organizationId, function () use ($returnRepository, $purchaseReturn): PurchaseReturn {
            $returnRepository->save($purchaseReturn);
            return $returnRepository->get($purchaseReturn->organizationId(), $purchaseReturn->id());
        });
        $restoredReturn->ship(ActorId::fromString(self::ACTOR, $this->ids), new DateTimeImmutable('2026-08-29T13:00:00Z'));
        $this->transactions->transactional($organizationId, fn() => $returnRepository->save($restoredReturn));
        self::assertSame(PurchaseReturnStatus::Shipped, $restoredReturn->status());
        self::assertSame('2.000000000000', $returnRepository->shippedQuantityByProduct($organizationId, $posted->id())[self::PRODUCT]->toString());
    }

    public function testShippingPurchaseReturnIsAtomicAndIdempotentWithRealPostgresRepositories(): void
    {
        $organizationId = OrganizationId::fromString(self::ORGANIZATION, $this->ids);
        $actorId = ActorId::fromString(self::ACTOR, $this->ids);
        $receipt = GoodsReceipt::create(
            GoodsReceiptId::fromString(self::RECEIPT, $this->ids),
            $organizationId,
            StoreId::fromString(self::STORE, $this->ids),
            SupplierId::fromString(self::SUPPLIER, $this->ids),
            null,
            GoodsReceiptNumber::fromString('GR-SHIP-RETURN-001'),
            null,
            'Return shipping fixture',
            $actorId,
            new DateTimeImmutable('2026-08-31T12:00:00Z'),
        );
        $receipt->addLine(new GoodsReceiptLine(
            GoodsReceiptLineId::fromString(self::LINE, $this->ids),
            $receipt->id(),
            ProductId::fromString(self::PRODUCT, $this->ids),
            ProductPackagingId::fromString(self::PACKAGING, $this->ids),
            $this->quantity('10'),
            $this->quantity('1'),
            $this->quantity('10'),
            $this->money('4'),
            $this->money('4'),
            null,
        ));
        $this->transactions->transactional($organizationId, function () use ($receipt, $actorId): void {
            $this->repository->save($receipt);
            $receipt->post($actorId, new DateTimeImmutable('2026-08-31T12:01:00Z'));
            $this->repository->save($receipt);
        });

        $returns = new DbalPurchaseReturnRepository($this->db, $this->ids, $this->decimals);
        $return = PurchaseReturn::create(PurchaseReturnId::fromString(self::PURCHASE_RETURN, $this->ids), $organizationId, StoreId::fromString(self::STORE, $this->ids), SupplierId::fromString(self::SUPPLIER, $this->ids), $receipt->id(), null, 'Damaged goods', $actorId, new DateTimeImmutable('2026-08-31T12:02:00Z'));
        $return->addLine(new PurchaseReturnLine(PurchaseReturnLineId::fromString(self::PURCHASE_RETURN_LINE, $this->ids), $return->id(), ProductId::fromString(self::PRODUCT, $this->ids), $this->quantity('4'), $receipt->lines()[0]->id()));
        $this->transactions->transactional($organizationId, fn() => $returns->save($return));

        $this->db->executeStatement('INSERT INTO inventory.stock (id,organization_id,store_id,product_id,quantity_on_hand,initialized,initialized_at,initialized_by,version) VALUES (?,?,?,?,10,TRUE,NOW(),?,1)', [self::STOCK, self::ORGANIZATION, self::STORE, self::PRODUCT, self::ACTOR]);
        $this->db->executeStatement("INSERT INTO inventory_costing.stock_valuation (id,organization_id,store_id,product_id,stock_id,quantity_on_hand,total_value,currency,version) VALUES (?,?,?,?,?,10,40,'XAF',1)", [self::VALUATION, self::ORGANIZATION, self::STORE, self::PRODUCT, self::STOCK]);

        $handler = $this->shipHandler();
        $command = new ShipPurchaseReturn($return->id(), $this->actorContext(), 'purchase-return-retry');
        $shipped = $handler($command);
        $replayed = $handler($command);

        self::assertSame(PurchaseReturnStatus::Shipped, $shipped->status());
        self::assertSame(PurchaseReturnStatus::Shipped, $replayed->status());
        self::assertSame('6.000000000000', (string) $this->db->fetchOne('SELECT quantity_on_hand FROM inventory.stock WHERE id = ?', [self::STOCK]));
        self::assertSame('24.000000', (string) $this->db->fetchOne('SELECT total_value FROM inventory_costing.stock_valuation WHERE id = ?', [self::VALUATION]));
        self::assertSame(1, (int) $this->db->fetchOne("SELECT COUNT(*) FROM inventory.stock_movement WHERE organization_id = ? AND type = 'PURCHASE_RETURN'", [self::ORGANIZATION]));
        self::assertSame(1, (int) $this->db->fetchOne("SELECT COUNT(*) FROM inventory_costing.stock_valuation_movement WHERE organization_id = ? AND type = 'PURCHASE_RETURN'", [self::ORGANIZATION]));
        self::assertSame(1, (int) $this->db->fetchOne("SELECT COUNT(*) FROM security.security_audit_entries WHERE organization_id = ? AND action = 'PURCHASE_RETURN_SHIPPED'", [self::ORGANIZATION]));
        self::assertSame(1, (int) $this->db->fetchOne("SELECT COUNT(*) FROM messaging.outbox_messages WHERE organization_id = ? AND type = 'purchasing.purchase_return_shipped.v1'", [self::ORGANIZATION]));
    }

    public function testShippingPurchaseReturnRollsBackEveryEffectWhenOutboxFails(): void
    {
        $return = $this->prepareShippablePurchaseReturn();

        try {
            ($this->shipHandler(new FailingPurchaseReturnOutbox()))(new ShipPurchaseReturn($return->id(), $this->actorContext(), 'purchase-return-failure'));
            self::fail('The injected outbox failure must abort the purchase return shipment.');
        } catch (RuntimeException $exception) {
            self::assertSame('Injected purchase return outbox failure.', $exception->getMessage());
        }

        self::assertSame(PurchaseReturnStatus::Draft, (new DbalPurchaseReturnRepository($this->db, $this->ids, $this->decimals))->get(OrganizationId::fromString(self::ORGANIZATION, $this->ids), $return->id())->status());
        self::assertSame('10.000000000000', (string) $this->db->fetchOne('SELECT quantity_on_hand FROM inventory.stock WHERE id = ?', [self::STOCK]));
        self::assertSame('40.000000', (string) $this->db->fetchOne('SELECT total_value FROM inventory_costing.stock_valuation WHERE id = ?', [self::VALUATION]));
        self::assertSame(0, (int) $this->db->fetchOne("SELECT COUNT(*) FROM inventory.stock_movement WHERE organization_id = ? AND type = 'PURCHASE_RETURN'", [self::ORGANIZATION]));
        self::assertSame(0, (int) $this->db->fetchOne("SELECT COUNT(*) FROM inventory_costing.stock_valuation_movement WHERE organization_id = ? AND type = 'PURCHASE_RETURN'", [self::ORGANIZATION]));
        self::assertSame(0, (int) $this->db->fetchOne("SELECT COUNT(*) FROM security.security_audit_entries WHERE organization_id = ? AND action = 'PURCHASE_RETURN_SHIPPED'", [self::ORGANIZATION]));
        self::assertSame(0, (int) $this->db->fetchOne("SELECT COUNT(*) FROM messaging.outbox_messages WHERE organization_id = ? AND type = 'purchasing.purchase_return_shipped.v1'", [self::ORGANIZATION]));
    }

    public function testPurchaseReturnLockSerializesConcurrentShipmentAttempts(): void
    {
        $this->db->executeStatement("INSERT INTO purchasing.purchase_return (id,organization_id,source_store_id,supplier_id,status,reason,created_by,created_at,version) VALUES (?,?,?,?,'DRAFT','Concurrent shipment',?,NOW(),1)", [self::PURCHASE_RETURN, self::ORGANIZATION, self::STORE, self::SUPPLIER, self::ACTOR]);
        $second = DriverManager::getConnection($this->db->getParams());
        $this->db->beginTransaction();
        $this->tenantContext($this->db);
        self::assertSame(self::PURCHASE_RETURN, $this->db->fetchOne('SELECT id FROM purchasing.purchase_return WHERE id = ? FOR UPDATE', [self::PURCHASE_RETURN]));

        $second->beginTransaction();
        $this->tenantContext($second);
        $second->executeStatement("SET LOCAL lock_timeout = '100ms'");
        $blocked = false;
        try {
            $second->fetchOne('SELECT id FROM purchasing.purchase_return WHERE id = ? FOR UPDATE', [self::PURCHASE_RETURN]);
        } catch (Throwable) {
            $blocked = true;
        } finally {
            $second->rollBack();
        }
        self::assertTrue($blocked, 'A concurrent shipment must wait for the purchase return lock.');
        $this->db->commit();

        $second->beginTransaction();
        $this->tenantContext($second);
        self::assertSame(self::PURCHASE_RETURN, $second->fetchOne('SELECT id FROM purchasing.purchase_return WHERE id = ? FOR UPDATE', [self::PURCHASE_RETURN]));
        $second->rollBack();
        $second->close();
    }

    public function testRuntimeRoleCannotReadPurchaseReturnFromAnotherTenant(): void
    {
        $this->db->executeStatement("INSERT INTO purchasing.purchase_return (id,organization_id,source_store_id,supplier_id,status,reason,created_by,created_at,version) VALUES (?,?,?,?,'DRAFT','Tenant isolation',?,NOW(),1)", [self::PURCHASE_RETURN, self::ORGANIZATION, self::STORE, self::SUPPLIER, self::ACTOR]);
        $this->db->beginTransaction();
        $this->tenantContext($this->db, self::OTHER_ORGANIZATION);

        self::assertSame(0, (int) $this->db->fetchOne('SELECT COUNT(*) FROM purchasing.purchase_return WHERE id = ?', [self::PURCHASE_RETURN]));

        $this->db->rollBack();
    }

    private function prepareShippablePurchaseReturn(): PurchaseReturn
    {
        $organizationId = OrganizationId::fromString(self::ORGANIZATION, $this->ids);
        $actorId = ActorId::fromString(self::ACTOR, $this->ids);
        $receipt = GoodsReceipt::create(GoodsReceiptId::fromString(self::RECEIPT, $this->ids), $organizationId, StoreId::fromString(self::STORE, $this->ids), SupplierId::fromString(self::SUPPLIER, $this->ids), null, GoodsReceiptNumber::fromString('GR-RETURN-ROLLBACK-001'), null, 'Return rollback fixture', $actorId, new DateTimeImmutable('2026-08-31T12:00:00Z'));
        $receipt->addLine(new GoodsReceiptLine(GoodsReceiptLineId::fromString(self::LINE, $this->ids), $receipt->id(), ProductId::fromString(self::PRODUCT, $this->ids), ProductPackagingId::fromString(self::PACKAGING, $this->ids), $this->quantity('10'), $this->quantity('1'), $this->quantity('10'), $this->money('4'), $this->money('4'), null));
        $this->transactions->transactional($organizationId, function () use ($receipt, $actorId): void {
            $this->repository->save($receipt);
            $receipt->post($actorId, new DateTimeImmutable('2026-08-31T12:01:00Z'));
            $this->repository->save($receipt);
        });

        $return = PurchaseReturn::create(PurchaseReturnId::fromString(self::PURCHASE_RETURN, $this->ids), $organizationId, StoreId::fromString(self::STORE, $this->ids), SupplierId::fromString(self::SUPPLIER, $this->ids), $receipt->id(), null, 'Damaged goods', $actorId, new DateTimeImmutable('2026-08-31T12:02:00Z'));
        $return->addLine(new PurchaseReturnLine(PurchaseReturnLineId::fromString(self::PURCHASE_RETURN_LINE, $this->ids), $return->id(), ProductId::fromString(self::PRODUCT, $this->ids), $this->quantity('4'), $receipt->lines()[0]->id()));
        $this->transactions->transactional($organizationId, fn() => (new DbalPurchaseReturnRepository($this->db, $this->ids, $this->decimals))->save($return));
        $this->db->executeStatement('INSERT INTO inventory.stock (id,organization_id,store_id,product_id,quantity_on_hand,initialized,initialized_at,initialized_by,version) VALUES (?,?,?,?,10,TRUE,NOW(),?,1)', [self::STOCK, self::ORGANIZATION, self::STORE, self::PRODUCT, self::ACTOR]);
        $this->db->executeStatement("INSERT INTO inventory_costing.stock_valuation (id,organization_id,store_id,product_id,stock_id,quantity_on_hand,total_value,currency,version) VALUES (?,?,?,?,?,10,40,'XAF',1)", [self::VALUATION, self::ORGANIZATION, self::STORE, self::PRODUCT, self::STOCK]);

        return $return;
    }

    private function shipHandler(?OutboxRepository $outbox = null): ShipPurchaseReturnHandler
    {
        $container = self::getContainer();

        return new ShipPurchaseReturnHandler(
            $container->get(\Zandu\Modules\Purchasing\Domain\PurchaseReturn\PurchaseReturnRepository::class),
            $container->get(GoodsReceiptRepository::class),
            $container->get(\Zandu\Modules\Purchasing\Domain\GoodsReceiptCorrection\GoodsReceiptCorrectionRepository::class),
            $container->get(InventoryPurchaseReturnShipper::class),
            new DoctrineTenantTransaction($this->db, 'zandu_runtime'),
            $this->createStub(AuthorizationService::class),
            $this->createStub(OperationalGuard::class),
            $container->get(SecurityAuditTrail::class),
            $outbox ?? $container->get(OutboxRepository::class),
            new SymfonyUuidV7Generator(),
            new FrozenClock(new DateTimeImmutable('2026-08-31T12:03:00Z')),
        );
    }

    private function actorContext(): \Zandu\SharedKernel\Context\ActorContext
    {
        return new \Zandu\SharedKernel\Context\ActorContext(
            ActorId::fromString(self::ACTOR, $this->ids),
            OrganizationId::fromString(self::ORGANIZATION, $this->ids),
            \Zandu\SharedKernel\Context\ActorType::User,
            \Zandu\SharedKernel\Messaging\CorrelationId::fromString('0198da20-0000-7000-8000-000000000099', $this->ids),
            new DateTimeImmutable('2026-08-31T12:03:00Z'),
        );
    }

    private function tenantContext(Connection $connection, string $organizationId = self::ORGANIZATION): void
    {
        $connection->executeStatement('SET LOCAL ROLE zandu_runtime');
        $connection->executeStatement("SELECT set_config('app.organization_id', ?, true)", [$organizationId]);
    }

    private function fixtures(): void
    {
        $this->db->executeStatement("INSERT INTO organization.organizations (id,name,status,country_code,default_currency,default_time_zone,default_locale,created_by,created_at,updated_by,updated_at,version) VALUES (?, 'Receipt tenant','ACTIVE','CG','XAF','Africa/Brazzaville','fr_CG',?,NOW(),?,NOW(),1)", [self::ORGANIZATION, self::ACTOR, self::ACTOR]);
        $this->db->executeStatement("INSERT INTO organization.stores (id,organization_id,code,name,status,time_zone,currency,locale,created_by,created_at,updated_by,updated_at,version) VALUES (?,?,'MAIN','Main','ACTIVE','Africa/Brazzaville','XAF','fr_CG',?,NOW(),?,NOW(),1)", [self::STORE, self::ORGANIZATION, self::ACTOR, self::ACTOR]);
        $this->db->executeStatement("INSERT INTO purchasing.supplier (id,organization_id,name,status,created_at,created_by,version) VALUES (?,?,'Supplier','ACTIVE',NOW(),?,1)", [self::SUPPLIER, self::ORGANIZATION, self::ACTOR]);
        $this->db->executeStatement("INSERT INTO catalog.units_of_measure (id,organization_id,code,name,dimension,precision,rounding_mode,status,version) VALUES (?,?,'EA','Article','COUNT',0,'HalfUp','ACTIVE',1)", [self::UNIT, self::ORGANIZATION]);
        $this->db->executeStatement("INSERT INTO catalog.products (id,organization_id,product_code,name,status,type,base_unit_id,inventory_tracked,created_at,created_by,activated_at,activated_by,version) VALUES (?,?,'SKU','Product','ACTIVE','PHYSICAL',?,TRUE,NOW(),?,NOW(),?,1)", [self::PRODUCT, self::ORGANIZATION, self::UNIT, self::ACTOR, self::ACTOR]);
        $this->db->executeStatement("INSERT INTO catalog.product_packagings (id,organization_id,product_id,base,code,name,unit_id,conversion_factor,precision,minimum_quantity,quantity_increment,allowed_for_sale,allowed_for_purchase,status,created_at,created_by,version) VALUES (?,?,?,FALSE,'CASE','Carton',?,12,0,1,1,TRUE,TRUE,'ACTIVE',NOW(),?,1)", [self::PACKAGING, self::ORGANIZATION, self::PRODUCT, self::UNIT, self::ACTOR]);
    }

    private function cleanup(): void
    {
        foreach (['messaging.outbox_messages', 'security.security_audit_entries', 'inventory_costing.stock_valuation_movement', 'inventory_costing.stock_valuation', 'inventory.stock_movement', 'inventory.stock'] as $table) {
            $this->db->executeStatement(sprintf('DELETE FROM %s WHERE organization_id = ?', $table), [self::ORGANIZATION]);
        }
        $this->db->executeStatement("UPDATE purchasing.goods_receipt_correction SET status = 'DRAFT', posted_by = NULL, posted_at = NULL WHERE organization_id = ?", [self::ORGANIZATION]);
        $this->db->executeStatement("UPDATE purchasing.purchase_return SET status = 'DRAFT', shipped_by = NULL, shipped_at = NULL, cancelled_by = NULL, cancelled_at = NULL WHERE organization_id = ?", [self::ORGANIZATION]);
        $this->db->executeStatement("UPDATE purchasing.goods_receipt SET status = 'DRAFT', posted_by = NULL, posted_at = NULL, cancelled_by = NULL, cancelled_at = NULL WHERE organization_id = ?", [self::ORGANIZATION]);
        foreach (['purchasing.purchase_return_line', 'purchasing.purchase_return', 'purchasing.goods_receipt_correction_line', 'purchasing.goods_receipt_correction', 'purchasing.goods_receipt_line', 'purchasing.goods_receipt', 'purchasing.supplier', 'catalog.product_packagings', 'catalog.products', 'catalog.units_of_measure', 'organization.stores'] as $table) {
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

final class FailingPurchaseReturnOutbox implements OutboxRepository
{
    public function append(OutboxMessage $message): void
    {
        throw new RuntimeException('Injected purchase return outbox failure.');
    }
}
