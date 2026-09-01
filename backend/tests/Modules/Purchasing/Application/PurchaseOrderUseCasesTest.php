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
use Zandu\Modules\Purchasing\Application\AddPurchaseOrderLine\AddPurchaseOrderLine;
use Zandu\Modules\Purchasing\Application\AddPurchaseOrderLine\AddPurchaseOrderLineHandler;
use Zandu\Modules\Purchasing\Application\CancelPurchaseOrder\CancelPurchaseOrder;
use Zandu\Modules\Purchasing\Application\CancelPurchaseOrder\CancelPurchaseOrderHandler;
use Zandu\Modules\Purchasing\Application\ClosePurchaseOrder\ClosePurchaseOrder;
use Zandu\Modules\Purchasing\Application\ClosePurchaseOrder\ClosePurchaseOrderHandler;
use Zandu\Modules\Purchasing\Application\ConfirmPurchaseOrder\ConfirmPurchaseOrder;
use Zandu\Modules\Purchasing\Application\ConfirmPurchaseOrder\ConfirmPurchaseOrderHandler;
use Zandu\Modules\Purchasing\Application\CreatePurchaseOrder\CreatePurchaseOrder;
use Zandu\Modules\Purchasing\Application\CreatePurchaseOrder\CreatePurchaseOrderHandler;
use Zandu\Modules\Purchasing\Application\PurchaseOrderConfirmationValidator;
use Zandu\Modules\Purchasing\Application\PurchaseOrderLineFactory;
use Zandu\Modules\Purchasing\Application\RemovePurchaseOrderLine\RemovePurchaseOrderLine;
use Zandu\Modules\Purchasing\Application\RemovePurchaseOrderLine\RemovePurchaseOrderLineHandler;
use Zandu\Modules\Purchasing\Application\TenantPurchaseOrderLoader;
use Zandu\Modules\Purchasing\Application\TenantSupplierLoader;
use Zandu\Modules\Purchasing\Application\UpdatePurchaseOrderLine\UpdatePurchaseOrderLine;
use Zandu\Modules\Purchasing\Application\UpdatePurchaseOrderLine\UpdatePurchaseOrderLineHandler;
use Zandu\Modules\Purchasing\Domain\PurchaseOrder\PurchaseOrder;
use Zandu\Modules\Purchasing\Domain\PurchaseOrder\PurchaseOrderNotFound;
use Zandu\Modules\Purchasing\Domain\PurchaseOrder\PurchaseOrderRepository;
use Zandu\Modules\Purchasing\Domain\PurchaseOrder\PurchaseOrderStatus;
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
use Zandu\SharedKernel\Identity\IdGenerator;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\ProductPackagingId;
use Zandu\SharedKernel\Identity\PurchaseOrderId;
use Zandu\SharedKernel\Identity\StoreId;
use Zandu\SharedKernel\Identity\SupplierId;
use Zandu\SharedKernel\Identity\Uuid;
use Zandu\SharedKernel\Messaging\CorrelationId;
use Zandu\SharedKernel\Money\Currency;
use Zandu\SharedKernel\Money\Money;
use Zandu\SharedKernel\Quantity\Quantity;
use Zandu\SharedKernel\SecurityAudit\ResourceReference;
use Zandu\SharedKernel\SecurityAudit\SafeAuditMetadata;
use Zandu\SharedKernel\SecurityAudit\SecurityAction;
use Zandu\SharedKernel\SecurityAudit\SecurityAuditTrail;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\Tests\SharedKernel\Time\FrozenClock;

final class PurchaseOrderUseCasesTest extends TestCase
{
    private const ORGANIZATION = '0198db00-0000-7000-8000-000000000001';
    private const STORE = '0198db00-0000-7000-8000-000000000002';
    private const SUPPLIER = '0198db00-0000-7000-8000-000000000003';
    private const PRODUCT = '0198db00-0000-7000-8000-000000000004';
    private const PACKAGING = '0198db00-0000-7000-8000-000000000005';
    private const ORDER = '0198db00-0000-7000-8000-000000000006';
    private const LINE_A = '0198db00-0000-7000-8000-000000000007';
    private const LINE_B = '0198db00-0000-7000-8000-000000000008';
    private const ACTOR = '0198db00-0000-7000-8000-000000000009';
    private const CORRELATION = '0198db00-0000-7000-8000-000000000010';

    private SymfonyUuidFactory $ids;
    private BrickDecimalFactory $decimals;
    private PurchaseOrderUseCaseRepository $orders;
    private SupplierUseCaseRepositoryForOrders $suppliers;
    private MutablePurchasableCatalog $catalog;
    private RecordingPurchaseOrderAuthorization $authorization;
    private RecordingPurchaseOrderGuard $guard;
    private RecordingPurchaseOrderAudit $audit;
    private SequentialIdGenerator $idGenerator;
    private FrozenClock $clock;
    private DirectPurchaseOrderTransaction $transaction;

    protected function setUp(): void
    {
        $this->ids = new SymfonyUuidFactory();
        $this->decimals = new BrickDecimalFactory();
        $this->orders = new PurchaseOrderUseCaseRepository();
        $this->suppliers = new SupplierUseCaseRepositoryForOrders();
        $this->catalog = new MutablePurchasableCatalog($this->decimals);
        $this->authorization = new RecordingPurchaseOrderAuthorization();
        $this->guard = new RecordingPurchaseOrderGuard();
        $this->audit = new RecordingPurchaseOrderAudit();
        $this->idGenerator = new SequentialIdGenerator([
            $this->ids->fromString(self::ORDER),
            $this->ids->fromString(self::LINE_A),
            $this->ids->fromString(self::LINE_B),
        ]);
        $this->clock = new FrozenClock(new DateTimeImmutable('2026-08-29T08:00:00Z'));
        $this->transaction = new DirectPurchaseOrderTransaction();
        $this->suppliers->save(Supplier::create(
            $this->supplierId(),
            $this->organizationId(),
            SupplierName::fromString('Acme'),
            null,
            null,
            null,
            null,
            $this->actorId(),
            $this->clock->now(),
        ));
    }

    public function testDraftUseCasesCalculateSnapshotsAndMaintainTheAggregate(): void
    {
        $order = $this->create();
        $add = $this->addHandler();
        $order = $add(new AddPurchaseOrderLine($order->id(), $this->productId(), $this->packagingId(), $this->quantity('5'), $this->money('120'), $this->context()));

        self::assertSame('60.000000000000', $order->lines()[0]->orderedBaseQuantity()->toString());
        self::assertSame('10.000000000000', $order->lines()[0]->inventoryUnitCost()->amount()->toString());
        self::assertSame('600.000000', $order->expectedTotal()->amount()->toString());

        $order = $this->updateHandler()(new UpdatePurchaseOrderLine($order->id(), $order->lines()[0]->id(), $this->productId(), $this->packagingId(), $this->quantity('3'), $this->money('120'), $this->context()));
        self::assertSame('360.000000', $order->expectedTotal()->amount()->toString());

        $order = $this->removeHandler()(new RemovePurchaseOrderLine($order->id(), $order->lines()[0]->id(), $this->context()));
        self::assertSame(0, $order->lineCount());
        self::assertSame([
            PermissionCode::PurchaseOrderCreate,
            PermissionCode::PurchaseOrderUpdateDraft,
            PermissionCode::PurchaseOrderUpdateDraft,
            PermissionCode::PurchaseOrderUpdateDraft,
        ], $this->authorization->permissions);
    }

    public function testConfirmationRevalidatesCatalogSupplierAndStore(): void
    {
        $order = $this->createWithLine();
        $handler = $this->confirmHandler();

        $order = $handler(new ConfirmPurchaseOrder($order->id(), $this->context()));

        self::assertSame(PurchaseOrderStatus::Confirmed, $order->status());
        self::assertSame(PermissionCode::PurchaseOrderConfirm, array_slice($this->authorization->permissions, -1)[0]);
        self::assertSame(2, $this->catalog->calls);
        self::assertContains(OperationalMode::Standard, $this->guard->modes);
    }

    public function testConfirmationRejectsAStaleCatalogSnapshot(): void
    {
        $order = $this->createWithLine();
        $this->catalog->factor = '6';

        try {
            $this->confirmHandler()(new ConfirmPurchaseOrder($order->id(), $this->context()));
            self::fail('A stale conversion snapshot must be rejected.');
        } catch (PurchasingRuleViolation $exception) {
            self::assertSame('PURCHASE_ORDER_SNAPSHOT_STALE', $exception->errorCode());
        }
    }

    public function testCancellationUsesRemediationMode(): void
    {
        $order = $this->create();
        $handler = new CancelPurchaseOrderHandler($this->loader(), $this->orders, $this->clock, $this->transaction, $this->authorization, $this->guard);

        $order = $handler(new CancelPurchaseOrder($order->id(), $this->context()));

        self::assertSame(PurchaseOrderStatus::Cancelled, $order->status());
        self::assertSame(OperationalMode::Remediation, array_slice($this->guard->modes, -1)[0]);
    }

    public function testPartialCloseRequiresReasonAndWritesSecurityAudit(): void
    {
        $order = $this->confirmHandler()(new ConfirmPurchaseOrder($this->createWithLine()->id(), $this->context()));
        $order->recordReceipt($order->lines()[0]->id(), $this->quantity('1'), $this->actorId(), $this->clock->now());
        $this->orders->save($order);
        $handler = new ClosePurchaseOrderHandler($this->loader(), $this->orders, $this->clock, $this->transaction, $this->authorization, $this->guard, $this->audit);

        $order = $handler(new ClosePurchaseOrder($order->id(), 'Supplier shortage', $this->context()));

        self::assertSame(PurchaseOrderStatus::Closed, $order->status());
        self::assertSame([SecurityAction::PartialPurchaseOrderClosed], $this->audit->actions);
        self::assertSame('Supplier shortage', $this->audit->metadata[0]['reason']);
    }

    private function create(): PurchaseOrder
    {
        $handler = new CreatePurchaseOrderHandler(
            $this->orders,
            new TenantSupplierLoader($this->suppliers),
            new FixedStoreBusinessContextProvider(),
            $this->idGenerator,
            $this->decimals,
            $this->clock,
            $this->transaction,
            $this->authorization,
            $this->guard,
        );

        return $handler(new CreatePurchaseOrder($this->storeId(), $this->supplierId(), 'PO-001', 'XAF', $this->context()));
    }

    private function createWithLine(): PurchaseOrder
    {
        $order = $this->create();

        return $this->addHandler()(new AddPurchaseOrderLine($order->id(), $this->productId(), $this->packagingId(), $this->quantity('5'), $this->money('120'), $this->context()));
    }

    private function addHandler(): AddPurchaseOrderLineHandler
    {
        return new AddPurchaseOrderLineHandler($this->loader(), $this->lineFactory(), $this->orders, $this->transaction, $this->authorization, $this->guard);
    }

    private function updateHandler(): UpdatePurchaseOrderLineHandler
    {
        return new UpdatePurchaseOrderLineHandler($this->loader(), $this->lineFactory(), $this->orders, $this->transaction, $this->authorization, $this->guard);
    }

    private function removeHandler(): RemovePurchaseOrderLineHandler
    {
        return new RemovePurchaseOrderLineHandler($this->loader(), $this->orders, $this->transaction, $this->authorization, $this->guard);
    }

    private function confirmHandler(): ConfirmPurchaseOrderHandler
    {
        return new ConfirmPurchaseOrderHandler(
            $this->loader(),
            new PurchaseOrderConfirmationValidator(new TenantSupplierLoader($this->suppliers), $this->catalog, $this->guard),
            $this->orders,
            $this->clock,
            $this->transaction,
            $this->authorization,
        );
    }

    private function loader(): TenantPurchaseOrderLoader
    {
        return new TenantPurchaseOrderLoader($this->orders);
    }
    private function lineFactory(): PurchaseOrderLineFactory
    {
        return new PurchaseOrderLineFactory($this->catalog, $this->idGenerator, $this->decimals);
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

final class PurchaseOrderUseCaseRepository implements PurchaseOrderRepository
{
    /** @var array<string, PurchaseOrder> */
    private array $orders = [];
    public function save(PurchaseOrder $purchaseOrder): void
    {
        $this->orders[$purchaseOrder->id()->toString()] = $purchaseOrder;
    }
    public function get(OrganizationId $organizationId, PurchaseOrderId $purchaseOrderId): PurchaseOrder
    {
        return $this->find($organizationId, $purchaseOrderId) ?? throw PurchaseOrderNotFound::withId($purchaseOrderId);
    }
    public function getForUpdate(OrganizationId $organizationId, PurchaseOrderId $purchaseOrderId): PurchaseOrder
    {
        return $this->get($organizationId, $purchaseOrderId);
    }
    public function find(OrganizationId $organizationId, PurchaseOrderId $purchaseOrderId): ?PurchaseOrder
    {
        $order = $this->orders[$purchaseOrderId->toString()] ?? null;

        return $order instanceof PurchaseOrder && $order->organizationId()->equals($organizationId) ? $order : null;
    }
    public function hasOpenForStore(OrganizationId $organizationId, StoreId $storeId): bool
    {
        return false;
    }
}

final class SupplierUseCaseRepositoryForOrders implements SupplierRepository
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

final class MutablePurchasableCatalog implements PurchasableProductSnapshotProvider
{
    public string $factor = '12';
    public int $calls = 0;
    public function __construct(private readonly BrickDecimalFactory $decimals) {}
    public function provide(OrganizationId $organizationId, ProductId $productId, ?ProductPackagingId $packagingId): PurchasableProductSnapshot
    {
        ++$this->calls;

        return new PurchasableProductSnapshot($productId, $packagingId, $this->decimals->fromString($this->factor), 1, 1);
    }
}

final class SequentialIdGenerator implements IdGenerator
{
    /** @param list<Uuid> $ids */
    public function __construct(private array $ids) {}
    public function generate(): Uuid
    {
        return array_shift($this->ids) ?? throw new \LogicException('No generated ID remains.');
    }
}

final class RecordingPurchaseOrderAuthorization implements AuthorizationService
{
    /** @var list<PermissionCode> */ public array $permissions = [];
    public function authorize(ActorContext $actorContext, PermissionCode $permission, ResourceScope $resourceScope): void
    {
        $this->permissions[] = $permission;
        TestCase::assertNotNull($resourceScope->storeId);
    }
}

final class RecordingPurchaseOrderGuard implements OperationalGuard
{
    /** @var list<OperationalMode> */ public array $modes = [];
    public function assertTenant(ActorContext $actorContext, OperationalMode $mode = OperationalMode::Standard): void
    {
        TestCase::fail('Purchase order use cases must be store-scoped.');
    }
    public function assertStore(ActorContext $actorContext, StoreId $storeId, OperationalMode $mode = OperationalMode::Standard): void
    {
        $this->modes[] = $mode;
    }
}

final class DirectPurchaseOrderTransaction implements TenantTransaction
{
    public function transactional(OrganizationId $organizationId, callable $operation): mixed
    {
        return $operation();
    }
}

final readonly class FixedStoreBusinessContextProvider implements StoreBusinessContextProvider
{
    public function provide(OrganizationId $organizationId, StoreId $storeId): StoreBusinessContext
    {
        return new StoreBusinessContext('Africa/Brazzaville', 'XAF');
    }
}

final class RecordingPurchaseOrderAudit implements SecurityAuditTrail
{
    /** @var list<SecurityAction> */ public array $actions = [];
    /** @var list<array<string, bool|float|int|string|null>> */ public array $metadata = [];
    public function recordSuccess(ActorContext $actorContext, SecurityAction $action, ResourceReference $target, SafeAuditMetadata $metadata, DateTimeImmutable $occurredAt, ?OrganizationId $organizationId = null): void
    {
        $this->actions[] = $action;
        $this->metadata[] = $metadata->toArray();
    }
    public function recordDenied(ActorContext $actorContext, ResourceReference $target, string $reason, SafeAuditMetadata $metadata, DateTimeImmutable $occurredAt): void {}
}
