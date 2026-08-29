<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Purchasing\Application;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\Catalog\Application\Contract\{PurchasableProductSnapshot, PurchasableProductSnapshotProvider};
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Inventory\Application\Contract\{GoodsReceiptStockResult, InventoryGoodsReceiver, ReceiveSupplierGoods, ReceivedGoodsItem};
use Zandu\Modules\Organization\Application\Contract\{OperationalGuard, StoreBusinessContext, StoreBusinessContextProvider};
use Zandu\Modules\Purchasing\Application\PostGoodsReceipt\{PostGoodsReceipt, PostGoodsReceiptHandler};
use Zandu\Modules\Purchasing\Application\TenantSupplierLoader;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\{GoodsReceipt, GoodsReceiptLine, GoodsReceiptNumber, GoodsReceiptRepository, GoodsReceiptStatus};
use Zandu\Modules\Purchasing\Domain\PurchaseOrder\PurchaseOrderRepository;
use Zandu\Modules\Purchasing\Domain\PurchasingRuleViolation;
use Zandu\Modules\Purchasing\Domain\Supplier\{Supplier, SupplierName, SupplierRepository};
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\{SymfonyUuidFactory, SymfonyUuidV7Generator};
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Context\{ActorContext, ActorType};
use Zandu\SharedKernel\Identity\{ActorId, GoodsReceiptId, GoodsReceiptLineId, OrganizationId, ProductId, StoreId, SupplierId};
use Zandu\SharedKernel\Messaging\{CorrelationId, OutboxMessage, OutboxRepository};
use Zandu\SharedKernel\Money\{Currency, Money};
use Zandu\SharedKernel\Quantity\Quantity;
use Zandu\SharedKernel\SecurityAudit\{SecurityAction, SecurityAuditTrail};
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\Tests\SharedKernel\Time\FrozenClock;

final class PostGoodsReceiptTest extends TestCase
{
    private SymfonyUuidFactory $ids;
    private BrickDecimalFactory $decimals;
    private FrozenClock $clock;

    protected function setUp(): void
    {
        $this->ids = new SymfonyUuidFactory();
        $this->decimals = new BrickDecimalFactory();
        $this->clock = new FrozenClock(new DateTimeImmutable('2026-08-29T12:00:00Z'));
    }

    public function testItPostsAReceiptWithInventoryAuditAndOutboxAtomically(): void
    {
        $receipt = $this->receipt();
        $receipts = $this->createMock(GoodsReceiptRepository::class);
        $receipts->expects(self::once())->method('getForUpdate')->willReturn($receipt);
        $receipts->expects(self::once())->method('save')->with($receipt);
        $inventory = $this->createMock(InventoryGoodsReceiver::class);
        $inventory->expects(self::once())->method('receive')->with(self::callback(fn(ReceiveSupplierGoods $request): bool => $request->goodsReceiptId->equals($receipt->id())
            && '5' === $request->items[0]['baseQuantity']->toString()
            && '10' === $request->items[0]['inventoryUnitCost']->amount()->toString()))->willReturn(new GoodsReceiptStockResult($receipt->id(), false, [
                new ReceivedGoodsItem($this->productId(), \Zandu\SharedKernel\Identity\StockId::fromString('0198dd00-0000-7000-8000-000000000009', $this->ids), \Zandu\SharedKernel\Identity\StockMovementId::fromString('0198dd00-0000-7000-8000-000000000010', $this->ids), $this->quantity('5'), $this->quantity('0'), $this->quantity('5')),
            ]));
        $authorization = $this->createMock(AuthorizationService::class);
        $authorization->expects(self::once())->method('authorize')->with($this->context(), PermissionCode::GoodsReceiptPost, self::anything());
        $audit = $this->createMock(SecurityAuditTrail::class);
        $audit->expects(self::once())->method('recordSuccess')->with($this->context(), SecurityAction::GoodsReceiptPosted, self::anything(), self::anything(), $this->clock->now());
        $outbox = $this->createMock(OutboxRepository::class);
        $outbox->expects(self::once())->method('append')->with(self::callback(static fn(OutboxMessage $message): bool => 'purchasing.goods_receipt_posted.v1' === $message->type));

        $posted = $this->handler($receipts, $inventory, $authorization, $audit, $outbox)($this->command());

        self::assertSame(GoodsReceiptStatus::Posted, $posted->status());
        self::assertEquals($this->clock->now(), $posted->postedAt());
    }

    public function testReplayReturnsThePostedReceiptWithoutDuplicatingEffects(): void
    {
        $receipt = $this->receipt();
        $receipt->post($this->actorId(), $this->clock->now());
        $receipts = $this->createStub(GoodsReceiptRepository::class);
        $receipts->method('getForUpdate')->willReturn($receipt);
        $inventory = $this->createMock(InventoryGoodsReceiver::class);
        $inventory->expects(self::never())->method('receive');
        $audit = $this->createMock(SecurityAuditTrail::class);
        $audit->expects(self::never())->method('recordSuccess');
        $outbox = $this->createMock(OutboxRepository::class);
        $outbox->expects(self::never())->method('append');

        $replayed = $this->handler($receipts, $inventory, $this->createStub(AuthorizationService::class), $audit, $outbox)($this->command());

        self::assertSame($receipt, $replayed);
    }

    public function testItRejectsAStaleCatalogSnapshotBeforeInventory(): void
    {
        $receipt = $this->receipt();
        $receipts = $this->createMock(GoodsReceiptRepository::class);
        $receipts->method('getForUpdate')->willReturn($receipt);
        $receipts->expects(self::never())->method('save');
        $inventory = $this->createMock(InventoryGoodsReceiver::class);
        $inventory->expects(self::never())->method('receive');

        try {
            $this->handler($receipts, $inventory, $this->createStub(AuthorizationService::class), $this->createStub(SecurityAuditTrail::class), $this->createStub(OutboxRepository::class), '2')($this->command());
            self::fail('A stale conversion factor must be rejected before stock mutation.');
        } catch (PurchasingRuleViolation $exception) {
            self::assertSame('GOODS_RECEIPT_SNAPSHOT_STALE', $exception->errorCode());
            self::assertSame(GoodsReceiptStatus::Draft, $receipt->status());
        }
    }

    private function handler(
        GoodsReceiptRepository $receipts,
        InventoryGoodsReceiver $inventory,
        AuthorizationService $authorization,
        SecurityAuditTrail $audit,
        OutboxRepository $outbox,
        string $catalogFactor = '1',
    ): PostGoodsReceiptHandler {
        $suppliers = $this->createStub(SupplierRepository::class);
        $suppliers->method('get')->willReturn(Supplier::create($this->supplierId(), $this->organizationId(), SupplierName::fromString('Acme'), null, null, null, null, $this->actorId(), $this->clock->now()));
        $stores = $this->createStub(StoreBusinessContextProvider::class);
        $stores->method('provide')->willReturn(new StoreBusinessContext('Africa/Lagos', 'XAF'));
        $catalog = $this->createStub(PurchasableProductSnapshotProvider::class);
        $catalog->method('provide')->willReturn(new PurchasableProductSnapshot($this->productId(), null, $this->decimals->fromString($catalogFactor), 1, 0));

        return new PostGoodsReceiptHandler(
            $receipts,
            $this->createStub(PurchaseOrderRepository::class),
            new TenantSupplierLoader($suppliers),
            $stores,
            $catalog,
            $inventory,
            new ImmediateGoodsReceiptTransaction(),
            $authorization,
            $this->createStub(OperationalGuard::class),
            $audit,
            $outbox,
            new SymfonyUuidV7Generator(),
            $this->clock,
        );
    }

    private function receipt(): GoodsReceipt
    {
        $receipt = GoodsReceipt::create($this->receiptId(), $this->organizationId(), $this->storeId(), $this->supplierId(), null, GoodsReceiptNumber::fromString('GR-POST-001'), null, null, $this->actorId(), $this->clock->now());
        $receipt->addLine(new GoodsReceiptLine(
            GoodsReceiptLineId::fromString('0198dd00-0000-7000-8000-000000000007', $this->ids),
            $receipt->id(),
            $this->productId(),
            null,
            $this->quantity('5'),
            $this->quantity('1'),
            $this->quantity('5'),
            null,
            $this->money('10'),
            null,
        ));

        return $receipt;
    }

    private function command(): PostGoodsReceipt
    {
        return new PostGoodsReceipt($this->receiptId(), $this->context(), 'command-001');
    }

    private function context(): ActorContext
    {
        return new ActorContext($this->actorId(), $this->organizationId(), ActorType::User, CorrelationId::fromString('0198dd00-0000-7000-8000-000000000008', $this->ids), $this->clock->now());
    }

    private function organizationId(): OrganizationId
    {
        return OrganizationId::fromString('0198dd00-0000-7000-8000-000000000001', $this->ids);
    }
    private function storeId(): StoreId
    {
        return StoreId::fromString('0198dd00-0000-7000-8000-000000000002', $this->ids);
    }
    private function supplierId(): SupplierId
    {
        return SupplierId::fromString('0198dd00-0000-7000-8000-000000000003', $this->ids);
    }
    private function productId(): ProductId
    {
        return ProductId::fromString('0198dd00-0000-7000-8000-000000000004', $this->ids);
    }
    private function receiptId(): GoodsReceiptId
    {
        return GoodsReceiptId::fromString('0198dd00-0000-7000-8000-000000000005', $this->ids);
    }
    private function actorId(): ActorId
    {
        return ActorId::fromString('0198dd00-0000-7000-8000-000000000006', $this->ids);
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

final class ImmediateGoodsReceiptTransaction implements TenantTransaction
{
    public function transactional(OrganizationId $organizationId, callable $operation): mixed
    {
        return $operation();
    }
}
