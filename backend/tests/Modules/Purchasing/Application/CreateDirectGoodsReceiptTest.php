<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Purchasing\Application;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\Catalog\Application\Contract\PurchasableProductSnapshot;
use Zandu\Modules\Catalog\Application\Contract\PurchasableProductSnapshotProvider;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Modules\Organization\Application\Contract\OperationalMode;
use Zandu\Modules\Organization\Application\Contract\StoreBusinessContext;
use Zandu\Modules\Organization\Application\Contract\StoreBusinessContextProvider;
use Zandu\Modules\Purchasing\Application\ConfiguredPurchasingPolicy;
use Zandu\Modules\Purchasing\Application\CreateDirectGoodsReceipt\CreateDirectGoodsReceipt;
use Zandu\Modules\Purchasing\Application\CreateDirectGoodsReceipt\CreateDirectGoodsReceiptHandler;
use Zandu\Modules\Purchasing\Application\CreateDirectGoodsReceipt\DirectGoodsReceiptLine;
use Zandu\Modules\Purchasing\Application\GoodsReceiptLineFactory;
use Zandu\Modules\Purchasing\Application\TenantSupplierLoader;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\GoodsReceipt;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\GoodsReceiptNotFound;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\GoodsReceiptRepository;
use Zandu\Modules\Purchasing\Domain\PurchasingRuleViolation;
use Zandu\Modules\Purchasing\Domain\Supplier\Supplier;
use Zandu\Modules\Purchasing\Domain\Supplier\SupplierName;
use Zandu\Modules\Purchasing\Domain\Supplier\SupplierNotFound;
use Zandu\Modules\Purchasing\Domain\Supplier\SupplierRepository;
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Context\ActorType;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\GoodsReceiptId;
use Zandu\SharedKernel\Identity\IdGenerator;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\ProductPackagingId;
use Zandu\SharedKernel\Identity\StoreId;
use Zandu\SharedKernel\Identity\SupplierId;
use Zandu\SharedKernel\Identity\Uuid;
use Zandu\SharedKernel\Messaging\CorrelationId;
use Zandu\SharedKernel\Money\Currency;
use Zandu\SharedKernel\Money\Money;
use Zandu\SharedKernel\Quantity\Quantity;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\Tests\SharedKernel\Time\FrozenClock;

final class CreateDirectGoodsReceiptTest extends TestCase
{
    private const ORGANIZATION = '0198dc00-0000-7000-8000-000000000001';
    private const STORE = '0198dc00-0000-7000-8000-000000000002';
    private const SUPPLIER = '0198dc00-0000-7000-8000-000000000003';
    private const PRODUCT = '0198dc00-0000-7000-8000-000000000004';
    private const PACKAGING = '0198dc00-0000-7000-8000-000000000005';
    private const RECEIPT = '0198dc00-0000-7000-8000-000000000006';
    private const LINE = '0198dc00-0000-7000-8000-000000000007';
    private const ACTOR = '0198dc00-0000-7000-8000-000000000008';
    private const CORRELATION = '0198dc00-0000-7000-8000-000000000009';

    private SymfonyUuidFactory $ids;
    private BrickDecimalFactory $decimals;
    private DirectGoodsReceiptRepository $receipts;
    private RecordingGoodsReceiptAuthorization $authorization;
    private RecordingGoodsReceiptGuard $guard;
    private MutableGoodsReceiptStoreContext $stores;
    private FrozenClock $clock;

    protected function setUp(): void
    {
        $this->ids = new SymfonyUuidFactory();
        $this->decimals = new BrickDecimalFactory();
        $this->receipts = new DirectGoodsReceiptRepository();
        $this->authorization = new RecordingGoodsReceiptAuthorization();
        $this->guard = new RecordingGoodsReceiptGuard();
        $this->stores = new MutableGoodsReceiptStoreContext();
        $this->clock = new FrozenClock(new DateTimeImmutable('2026-08-29T10:00:00Z'));
    }

    public function testItCreatesACompleteDirectReceiptFromServerSnapshots(): void
    {
        $catalog = new DirectGoodsReceiptCatalog($this->decimals);
        $receipt = $this->handler(true, $catalog)($this->command([
            new DirectGoodsReceiptLine($this->productId(), $this->packagingId(), $this->quantity('5'), $this->money('10')),
        ]));

        self::assertNull($receipt->purchaseOrderId());
        self::assertSame('GR-DIRECT-001', $receipt->number()->value());
        self::assertSame('DN-42', $receipt->supplierDeliveryNote());
        self::assertSame('60.000000000000', $receipt->lines()[0]->receivedBaseQuantity()->toString());
        self::assertNull($receipt->lines()[0]->actualUnitCost());
        self::assertSame('10', $receipt->lines()[0]->inventoryUnitCost()->amount()->toString());
        self::assertSame(PermissionCode::GoodsReceiptCreate, $this->authorization->permission);
        self::assertSame(self::STORE, $this->authorization->scope?->storeId?->toString());
        self::assertSame(OperationalMode::Standard, $this->guard->mode);
        self::assertSame(1, $catalog->calls);
        self::assertSame($receipt, $this->receipts->get($this->organizationId(), $receipt->id()));
    }

    public function testItRejectsDirectReceiptsWhenPurchaseOrdersAreRequired(): void
    {
        try {
            $this->handler(false, new DirectGoodsReceiptCatalog($this->decimals))($this->command([
                new DirectGoodsReceiptLine($this->productId(), null, $this->quantity('1'), $this->money('10')),
            ]));
            self::fail('Direct receipt must be rejected by the deployment policy.');
        } catch (PurchasingRuleViolation $exception) {
            self::assertSame('GOODS_RECEIPT_PURCHASE_ORDER_REQUIRED', $exception->errorCode());
            self::assertSame(0, $this->receipts->count());
        }
    }

    public function testItRejectsAnEmptyDirectReceipt(): void
    {
        try {
            $this->handler(true, new DirectGoodsReceiptCatalog($this->decimals))($this->command([]));
            self::fail('A direct receipt must include its products.');
        } catch (PurchasingRuleViolation $exception) {
            self::assertSame('GOODS_RECEIPT_EMPTY', $exception->errorCode());
            self::assertSame(0, $this->receipts->count());
        }
    }

    public function testItRejectsACostInAnotherCurrencyThanTheStore(): void
    {
        try {
            $this->handler(true, new DirectGoodsReceiptCatalog($this->decimals))($this->command([
                new DirectGoodsReceiptLine($this->productId(), null, $this->quantity('1'), Money::fromString('10', Currency::fromCode('USD'), $this->decimals)),
            ]));
            self::fail('Receipt cost currency must match the store.');
        } catch (PurchasingRuleViolation $exception) {
            self::assertSame('GOODS_RECEIPT_CURRENCY_MISMATCH', $exception->errorCode());
            self::assertSame(0, $this->receipts->count());
        }
    }

    /** @param list<DirectGoodsReceiptLine> $lines */
    private function command(array $lines): CreateDirectGoodsReceipt
    {
        return new CreateDirectGoodsReceipt($this->storeId(), $this->supplierId(), 'GR-DIRECT-001', 'DN-42', 'Direct delivery', $lines, $this->context());
    }

    private function handler(bool $directAllowed, DirectGoodsReceiptCatalog $catalog): CreateDirectGoodsReceiptHandler
    {
        $suppliers = new DirectGoodsReceiptSupplierRepository();
        $suppliers->save(Supplier::create($this->supplierId(), $this->organizationId(), SupplierName::fromString('Acme'), null, null, null, null, $this->actorId(), $this->clock->now()));
        $ids = new DirectGoodsReceiptIdGenerator([
            $this->ids->fromString(self::RECEIPT),
            $this->ids->fromString(self::LINE),
        ]);

        return new CreateDirectGoodsReceiptHandler(
            $this->receipts,
            new TenantSupplierLoader($suppliers),
            $this->stores,
            new GoodsReceiptLineFactory($catalog, $ids),
            new ConfiguredPurchasingPolicy(!$directAllowed, 'FORBIDDEN'),
            $ids,
            $this->clock,
            new DirectGoodsReceiptTransaction(),
            $this->authorization,
            $this->guard,
        );
    }

    private function context(): ActorContext
    {
        return new ActorContext($this->actorId(), $this->organizationId(), ActorType::User, CorrelationId::fromString(self::CORRELATION, $this->ids), $this->clock->now());
    }

    private function organizationId(): OrganizationId
    {
        return OrganizationId::fromString(self::ORGANIZATION, $this->ids);
    }

    private function storeId(): StoreId
    {
        return StoreId::fromString(self::STORE, $this->ids);
    }

    private function supplierId(): SupplierId
    {
        return SupplierId::fromString(self::SUPPLIER, $this->ids);
    }

    private function productId(): ProductId
    {
        return ProductId::fromString(self::PRODUCT, $this->ids);
    }

    private function packagingId(): ProductPackagingId
    {
        return ProductPackagingId::fromString(self::PACKAGING, $this->ids);
    }

    private function actorId(): ActorId
    {
        return ActorId::fromString(self::ACTOR, $this->ids);
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

final class DirectGoodsReceiptRepository implements GoodsReceiptRepository
{
    /** @var array<string, GoodsReceipt> */
    private array $receipts = [];

    public function save(GoodsReceipt $goodsReceipt): void
    {
        $this->receipts[$goodsReceipt->id()->toString()] = $goodsReceipt;
    }

    public function get(OrganizationId $organizationId, GoodsReceiptId $goodsReceiptId): GoodsReceipt
    {
        return $this->find($organizationId, $goodsReceiptId) ?? throw GoodsReceiptNotFound::withId($goodsReceiptId);
    }

    public function find(OrganizationId $organizationId, GoodsReceiptId $goodsReceiptId): ?GoodsReceipt
    {
        $receipt = $this->receipts[$goodsReceiptId->toString()] ?? null;

        return $receipt instanceof GoodsReceipt && $receipt->organizationId()->equals($organizationId) ? $receipt : null;
    }

    public function count(): int
    {
        return count($this->receipts);
    }
}

final class DirectGoodsReceiptSupplierRepository implements SupplierRepository
{
    /** @var array<string, Supplier> */
    private array $suppliers = [];

    public function save(Supplier $supplier): void
    {
        $this->suppliers[$supplier->id()->toString()] = $supplier;
    }

    public function get(OrganizationId $organizationId, SupplierId $supplierId): Supplier
    {
        return $this->find($organizationId, $supplierId) ?? throw SupplierNotFound::withId($supplierId);
    }

    public function find(OrganizationId $organizationId, SupplierId $supplierId): ?Supplier
    {
        $supplier = $this->suppliers[$supplierId->toString()] ?? null;

        return $supplier instanceof Supplier && $supplier->organizationId()->equals($organizationId) ? $supplier : null;
    }

    public function findAll(OrganizationId $organizationId): array
    {
        return [];
    }
}

final class DirectGoodsReceiptCatalog implements PurchasableProductSnapshotProvider
{
    public int $calls = 0;

    public function __construct(private readonly BrickDecimalFactory $decimals) {}

    public function provide(OrganizationId $organizationId, ProductId $productId, ?ProductPackagingId $packagingId): PurchasableProductSnapshot
    {
        ++$this->calls;

        return new PurchasableProductSnapshot($productId, $packagingId, $this->decimals->fromString('12'), 1, 1);
    }
}

final class DirectGoodsReceiptIdGenerator implements IdGenerator
{
    /** @param list<Uuid> $ids */
    public function __construct(private array $ids) {}

    public function generate(): Uuid
    {
        return array_shift($this->ids) ?? throw new \LogicException('No generated ID remains.');
    }
}

final class RecordingGoodsReceiptAuthorization implements AuthorizationService
{
    public ?PermissionCode $permission = null;
    public ?ResourceScope $scope = null;

    public function authorize(ActorContext $actorContext, PermissionCode $permission, ResourceScope $resourceScope): void
    {
        $this->permission = $permission;
        $this->scope = $resourceScope;
    }
}

final class RecordingGoodsReceiptGuard implements OperationalGuard
{
    public ?OperationalMode $mode = null;

    public function assertTenant(ActorContext $actorContext, OperationalMode $mode = OperationalMode::Standard): void
    {
        TestCase::fail('Goods receipt creation must be store-scoped.');
    }

    public function assertStore(ActorContext $actorContext, StoreId $storeId, OperationalMode $mode = OperationalMode::Standard): void
    {
        $this->mode = $mode;
    }
}

final class MutableGoodsReceiptStoreContext implements StoreBusinessContextProvider
{
    public string $currency = 'XAF';

    public function provide(OrganizationId $organizationId, StoreId $storeId): StoreBusinessContext
    {
        return new StoreBusinessContext('Africa/Brazzaville', $this->currency);
    }
}

final class DirectGoodsReceiptTransaction implements TenantTransaction
{
    public function transactional(OrganizationId $organizationId, callable $operation): mixed
    {
        return $operation();
    }
}
