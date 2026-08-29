<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Purchasing\Application;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\Catalog\Application\Contract\PurchasableProductSnapshotProvider;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Modules\Organization\Application\Contract\StoreBusinessContext;
use Zandu\Modules\Organization\Application\Contract\StoreBusinessContextProvider;
use Zandu\Modules\Purchasing\Application\CreateLinkedGoodsReceipt\CreateLinkedGoodsReceipt;
use Zandu\Modules\Purchasing\Application\CreateLinkedGoodsReceipt\CreateLinkedGoodsReceiptHandler;
use Zandu\Modules\Purchasing\Application\CreateLinkedGoodsReceipt\LinkedGoodsReceiptLine;
use Zandu\Modules\Purchasing\Application\GoodsReceiptLineFactory;
use Zandu\Modules\Purchasing\Application\TenantSupplierLoader;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\GoodsReceipt;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\GoodsReceiptRepository;
use Zandu\Modules\Purchasing\Domain\PurchaseOrder\PurchaseOrder;
use Zandu\Modules\Purchasing\Domain\PurchaseOrder\PurchaseOrderLine;
use Zandu\Modules\Purchasing\Domain\PurchaseOrder\PurchaseOrderNumber;
use Zandu\Modules\Purchasing\Domain\PurchaseOrder\PurchaseOrderRepository;
use Zandu\Modules\Purchasing\Domain\PurchasingRuleViolation;
use Zandu\Modules\Purchasing\Domain\Supplier\Supplier;
use Zandu\Modules\Purchasing\Domain\Supplier\SupplierName;
use Zandu\Modules\Purchasing\Domain\Supplier\SupplierRepository;
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Context\ActorType;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\GoodsReceiptId;
use Zandu\SharedKernel\Identity\GoodsReceiptLineId;
use Zandu\SharedKernel\Identity\IdGenerator;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\PurchaseOrderId;
use Zandu\SharedKernel\Identity\PurchaseOrderLineId;
use Zandu\SharedKernel\Identity\StoreId;
use Zandu\SharedKernel\Identity\SupplierId;
use Zandu\SharedKernel\Identity\Uuid;
use Zandu\SharedKernel\Messaging\CorrelationId;
use Zandu\SharedKernel\Money\Currency;
use Zandu\SharedKernel\Money\Money;
use Zandu\SharedKernel\Quantity\Quantity;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\Tests\SharedKernel\Time\FrozenClock;

final class CreateLinkedGoodsReceiptTest extends TestCase
{
    private SymfonyUuidFactory $ids;
    private BrickDecimalFactory $decimals;
    private FrozenClock $clock;

    protected function setUp(): void
    {
        $this->ids = new SymfonyUuidFactory();
        $this->decimals = new BrickDecimalFactory();
        $this->clock = new FrozenClock(new DateTimeImmutable('2026-08-29T14:00:00Z'));
    }

    public function testItCreatesALinkedPartialReceiptFromPurchaseOrderSnapshots(): void
    {
        $order = $this->confirmedOrder();
        $receipts = $this->createMock(GoodsReceiptRepository::class);
        $receipts->expects(self::once())->method('save')->with(self::callback(static fn(GoodsReceipt $receipt): bool => 1 === count($receipt->lines())));
        $authorization = $this->createMock(AuthorizationService::class);
        $authorization->expects(self::once())->method('authorize')->with($this->context(), PermissionCode::GoodsReceiptCreate, self::anything());

        $receipt = $this->handler($order, $receipts, $authorization)(new CreateLinkedGoodsReceipt(
            $order->id(),
            'GR-LINKED-001',
            'DN-77',
            'First partial delivery',
            [new LinkedGoodsReceiptLine($order->lines()[0]->id(), $this->quantity('5'), $this->money('24'))],
            $this->context(),
        ));

        self::assertTrue($receipt->purchaseOrderId()?->equals($order->id()));
        self::assertTrue($receipt->lines()[0]->purchaseOrderLineId()?->equals($order->lines()[0]->id()));
        self::assertSame('60.000000000000', $receipt->lines()[0]->receivedBaseQuantity()->toString());
        self::assertSame('24', $receipt->lines()[0]->actualUnitCost()?->amount()->toString());
        self::assertSame('2.000000000000', $receipt->lines()[0]->inventoryUnitCost()->amount()->toString());
        self::assertSame('0', $order->lines()[0]->receivedQuantity()->toString());
    }

    public function testItUsesTheOrderedInventoryCostWhenActualCostIsAbsent(): void
    {
        $order = $this->confirmedOrder();
        $receipt = $this->handler($order, $this->createStub(GoodsReceiptRepository::class), $this->createStub(AuthorizationService::class))(new CreateLinkedGoodsReceipt(
            $order->id(),
            'GR-LINKED-002',
            null,
            null,
            [new LinkedGoodsReceiptLine($order->lines()[0]->id(), $this->quantity('2'))],
            $this->context(),
        ));

        self::assertNull($receipt->lines()[0]->actualUnitCost());
        self::assertSame('10', $receipt->lines()[0]->inventoryUnitCost()->amount()->toString());
    }

    public function testItRejectsALineThatDoesNotBelongToTheOrder(): void
    {
        $order = $this->confirmedOrder();
        $receipts = $this->createMock(GoodsReceiptRepository::class);
        $receipts->expects(self::never())->method('save');

        $this->expectExceptionObject(PurchasingRuleViolation::with('GOODS_RECEIPT_PRODUCT_NOT_ORDERED', 'Every linked receipt line must belong to the purchase order.'));
        $this->handler($order, $receipts, $this->createStub(AuthorizationService::class))(new CreateLinkedGoodsReceipt(
            $order->id(),
            'GR-LINKED-003',
            null,
            null,
            [new LinkedGoodsReceiptLine(PurchaseOrderLineId::fromString('0198de00-0000-7000-8000-000000000099', $this->ids), $this->quantity('1'))],
            $this->context(),
        ));
    }

    public function testItRejectsAReceiptBeyondTheRemainingQuantity(): void
    {
        $order = $this->confirmedOrder();
        $order->recordReceipt($order->lines()[0]->id(), $this->quantity('110'), $this->actorId(), $this->clock->now());
        $receipts = $this->createMock(GoodsReceiptRepository::class);
        $receipts->expects(self::never())->method('save');

        try {
            $this->handler($order, $receipts, $this->createStub(AuthorizationService::class))(new CreateLinkedGoodsReceipt(
                $order->id(),
                'GR-LINKED-004',
                null,
                null,
                [new LinkedGoodsReceiptLine($order->lines()[0]->id(), $this->quantity('1'))],
                $this->context(),
            ));
            self::fail('A linked receipt must not exceed the remaining ordered quantity.');
        } catch (PurchasingRuleViolation $exception) {
            self::assertSame('OVER_RECEIPT_NOT_ALLOWED', $exception->errorCode());
        }
    }

    private function handler(PurchaseOrder $order, GoodsReceiptRepository $receipts, AuthorizationService $authorization): CreateLinkedGoodsReceiptHandler
    {
        $orders = $this->createStub(PurchaseOrderRepository::class);
        $orders->method('getForUpdate')->willReturn($order);
        $suppliers = $this->createStub(SupplierRepository::class);
        $suppliers->method('get')->willReturn(Supplier::create($this->supplierId(), $this->organizationId(), SupplierName::fromString('Acme'), null, null, null, null, $this->actorId(), $this->clock->now()));
        $stores = $this->createStub(StoreBusinessContextProvider::class);
        $stores->method('provide')->willReturn(new StoreBusinessContext('Africa/Lagos', 'XAF'));
        $generatedIds = new LinkedReceiptIdGenerator([
            $this->ids->fromString('0198de00-0000-7000-8000-000000000010'),
            $this->ids->fromString('0198de00-0000-7000-8000-000000000011'),
        ]);

        return new CreateLinkedGoodsReceiptHandler(
            $receipts,
            $orders,
            new TenantSupplierLoader($suppliers),
            $stores,
            new GoodsReceiptLineFactory($this->createStub(PurchasableProductSnapshotProvider::class), $generatedIds),
            $generatedIds,
            $this->clock,
            new LinkedReceiptTransaction(),
            $authorization,
            $this->createStub(OperationalGuard::class),
        );
    }

    private function confirmedOrder(): PurchaseOrder
    {
        $order = PurchaseOrder::create(
            PurchaseOrderId::fromString('0198de00-0000-7000-8000-000000000004', $this->ids),
            $this->organizationId(),
            $this->storeId(),
            $this->supplierId(),
            PurchaseOrderNumber::fromString('PO-001'),
            Currency::fromCode('XAF'),
            $this->money('0'),
            $this->actorId(),
            $this->clock->now(),
        );
        $order->addLine(new PurchaseOrderLine(
            PurchaseOrderLineId::fromString('0198de00-0000-7000-8000-000000000005', $this->ids),
            $order->id(),
            ProductId::fromString('0198de00-0000-7000-8000-000000000006', $this->ids),
            null,
            $this->quantity('10'),
            $this->quantity('12'),
            $this->quantity('120'),
            $this->money('120'),
            $this->money('10'),
            $this->quantity('0'),
        ));
        $order->confirm($this->actorId(), $this->clock->now());

        return $order;
    }

    private function context(): ActorContext
    {
        return new ActorContext($this->actorId(), $this->organizationId(), ActorType::User, CorrelationId::fromString('0198de00-0000-7000-8000-000000000009', $this->ids), $this->clock->now());
    }

    private function organizationId(): OrganizationId
    {
        return OrganizationId::fromString('0198de00-0000-7000-8000-000000000001', $this->ids);
    }
    private function storeId(): StoreId
    {
        return StoreId::fromString('0198de00-0000-7000-8000-000000000002', $this->ids);
    }
    private function supplierId(): SupplierId
    {
        return SupplierId::fromString('0198de00-0000-7000-8000-000000000003', $this->ids);
    }
    private function actorId(): ActorId
    {
        return ActorId::fromString('0198de00-0000-7000-8000-000000000007', $this->ids);
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

final class LinkedReceiptIdGenerator implements IdGenerator
{
    /** @param list<Uuid> $ids */
    public function __construct(private array $ids) {}
    public function generate(): Uuid
    {
        return array_shift($this->ids) ?? throw new \LogicException('No generated ID remains.');
    }
}

final class LinkedReceiptTransaction implements TenantTransaction
{
    public function transactional(OrganizationId $organizationId, callable $operation): mixed
    {
        return $operation();
    }
}
