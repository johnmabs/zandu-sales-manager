<?php

declare(strict_types=1);

namespace Zandu\Tests\Integration\Purchasing;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\GoodsReceipt;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\GoodsReceiptLine;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\GoodsReceiptNumber;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\GoodsReceiptRepository;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\GoodsReceiptStatus;
use Zandu\Modules\Purchasing\Domain\GoodsReceiptCorrection\GoodsReceiptCorrection;
use Zandu\Modules\Purchasing\Domain\GoodsReceiptCorrection\GoodsReceiptCorrectionLine;
use Zandu\Modules\Purchasing\Domain\GoodsReceiptCorrection\GoodsReceiptCorrectionStatus;
use Zandu\Modules\Purchasing\Infrastructure\Persistence\DbalGoodsReceiptCorrectionRepository;
use Zandu\Modules\Purchasing\Infrastructure\Persistence\DbalGoodsReceiptRepository;
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\Platform\Persistence\DoctrineTenantTransaction;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\GoodsReceiptCorrectionId;
use Zandu\SharedKernel\Identity\GoodsReceiptId;
use Zandu\SharedKernel\Identity\GoodsReceiptLineId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\ProductPackagingId;
use Zandu\SharedKernel\Identity\StoreId;
use Zandu\SharedKernel\Identity\SupplierId;
use Zandu\SharedKernel\Money\Currency;
use Zandu\SharedKernel\Money\Money;
use Zandu\SharedKernel\Quantity\Quantity;

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
        $this->db->executeStatement("UPDATE purchasing.goods_receipt_correction SET status = 'DRAFT', posted_by = NULL, posted_at = NULL WHERE organization_id = ?", [self::ORGANIZATION]);
        $this->db->executeStatement("UPDATE purchasing.goods_receipt SET status = 'DRAFT', posted_by = NULL, posted_at = NULL, cancelled_by = NULL, cancelled_at = NULL WHERE organization_id = ?", [self::ORGANIZATION]);
        foreach (['purchasing.goods_receipt_correction_line', 'purchasing.goods_receipt_correction', 'purchasing.goods_receipt_line', 'purchasing.goods_receipt', 'purchasing.supplier', 'catalog.product_packagings', 'catalog.products', 'catalog.units_of_measure', 'organization.stores'] as $table) {
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
