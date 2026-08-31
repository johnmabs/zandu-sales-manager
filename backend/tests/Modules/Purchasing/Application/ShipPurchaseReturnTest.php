<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Purchasing\Application;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Inventory\Application\Contract\InventoryPurchaseReturnShipper;
use Zandu\Modules\Inventory\Application\Contract\PurchaseReturnStockUnavailable;
use Zandu\Modules\Inventory\Application\Contract\ShipPurchaseReturnStock;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Modules\Purchasing\Application\ShipPurchaseReturn\ShipPurchaseReturn;
use Zandu\Modules\Purchasing\Application\ShipPurchaseReturn\ShipPurchaseReturnHandler;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\GoodsReceipt;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\GoodsReceiptLine;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\GoodsReceiptNumber;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\GoodsReceiptRepository;
use Zandu\Modules\Purchasing\Domain\GoodsReceiptCorrection\GoodsReceiptCorrectionRepository;
use Zandu\Modules\Purchasing\Domain\PurchaseReturn\PurchaseReturn;
use Zandu\Modules\Purchasing\Domain\PurchaseReturn\PurchaseReturnLine;
use Zandu\Modules\Purchasing\Domain\PurchaseReturn\PurchaseReturnRepository;
use Zandu\Modules\Purchasing\Domain\PurchaseReturn\PurchaseReturnStatus;
use Zandu\Modules\Purchasing\Domain\PurchasingRuleViolation;
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\Platform\Identity\SymfonyUuidV7Generator;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Context\ActorType;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\GoodsReceiptId;
use Zandu\SharedKernel\Identity\GoodsReceiptLineId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\PurchaseReturnId;
use Zandu\SharedKernel\Identity\PurchaseReturnLineId;
use Zandu\SharedKernel\Identity\StoreId;
use Zandu\SharedKernel\Identity\SupplierId;
use Zandu\SharedKernel\Messaging\CorrelationId;
use Zandu\SharedKernel\Messaging\OutboxRepository;
use Zandu\SharedKernel\Money\Currency;
use Zandu\SharedKernel\Money\Money;
use Zandu\SharedKernel\Quantity\Quantity;
use Zandu\SharedKernel\SecurityAudit\SecurityAction;
use Zandu\SharedKernel\SecurityAudit\SecurityAuditTrail;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\Tests\SharedKernel\Time\FrozenClock;

final class ShipPurchaseReturnTest extends TestCase
{
    private SymfonyUuidFactory $ids;
    private BrickDecimalFactory $decimals;
    private FrozenClock $clock;

    protected function setUp(): void
    {
        $this->ids = new SymfonyUuidFactory();
        $this->decimals = new BrickDecimalFactory();
        $this->clock = new FrozenClock(new DateTimeImmutable('2026-08-31T11:00:00Z'));
    }

    public function testItRevalidatesAndShipsInventoryBeforeClosingTheReturn(): void
    {
        [$receipt, $return] = $this->documents('4');
        $returns = $this->createMock(PurchaseReturnRepository::class);
        $returns->expects(self::once())->method('getForUpdate')->willReturn($return);
        $returns->expects(self::once())->method('shippedQuantityByProduct')->willReturn([
            $this->productId()->toString() => $this->quantity('2'),
        ]);
        $returns->expects(self::once())->method('save')->with($return);
        $inventory = $this->createMock(InventoryPurchaseReturnShipper::class);
        $inventory->expects(self::once())->method('ship')->with(self::callback(
            fn(ShipPurchaseReturnStock $request): bool => '4' === $request->items[0]['baseQuantity']->toString()
                && $this->returnId()->equals($request->purchaseReturnId)
                && 'Damaged delivery' === $request->reason,
        ))->willReturn(1);
        $authorization = $this->createMock(AuthorizationService::class);
        $authorization->expects(self::once())->method('authorize')->with($this->context(), PermissionCode::PurchaseReturnShip, self::anything());
        $audit = $this->createMock(SecurityAuditTrail::class);
        $audit->expects(self::once())->method('recordSuccess')->with($this->context(), SecurityAction::PurchaseReturnShipped, self::anything(), self::anything(), $this->clock->now());
        $outbox = $this->createMock(OutboxRepository::class);
        $outbox->expects(self::once())->method('append');

        $shipped = $this->handler($returns, $this->receiptRepository($receipt), $this->corrections([]), $inventory, $authorization, $audit, $outbox)(new ShipPurchaseReturn($return->id(), $this->context(), 'return-command'));

        self::assertSame(PurchaseReturnStatus::Shipped, $shipped->status());
        self::assertEquals($this->clock->now(), $shipped->shippedAt());
    }

    public function testReplayDoesNotDuplicateInventoryAuditOrOutbox(): void
    {
        [, $return] = $this->documents('4');
        $return->ship($this->actorId(), $this->clock->now());
        $returns = $this->createStub(PurchaseReturnRepository::class);
        $returns->method('getForUpdate')->willReturn($return);
        $inventory = $this->createMock(InventoryPurchaseReturnShipper::class);
        $inventory->expects(self::never())->method('ship');

        $replayed = $this->handler(
            $returns,
            $this->createStub(GoodsReceiptRepository::class),
            $this->createStub(GoodsReceiptCorrectionRepository::class),
            $inventory,
            $this->createStub(AuthorizationService::class),
            $this->createStub(SecurityAuditTrail::class),
            $this->createStub(OutboxRepository::class),
        )(new ShipPurchaseReturn($return->id(), $this->context()));

        self::assertSame($return, $replayed);
    }

    public function testItRejectsAStaleReturnableBalanceBeforeInventory(): void
    {
        [$receipt, $return] = $this->documents('4');
        $returns = $this->createStub(PurchaseReturnRepository::class);
        $returns->method('getForUpdate')->willReturn($return);
        $returns->method('shippedQuantityByProduct')->willReturn([
            $this->productId()->toString() => $this->quantity('4'),
        ]);
        $inventory = $this->createMock(InventoryPurchaseReturnShipper::class);
        $inventory->expects(self::never())->method('ship');

        try {
            $this->handler(
                $returns,
                $this->receiptRepository($receipt),
                $this->corrections([$this->productId()->toString() => $this->quantity('-3')]),
                $inventory,
                $this->createStub(AuthorizationService::class),
                $this->createStub(SecurityAuditTrail::class),
                $this->createStub(OutboxRepository::class),
            )(new ShipPurchaseReturn($return->id(), $this->context()));
            self::fail('A stale returnable balance must be rejected.');
        } catch (PurchasingRuleViolation $exception) {
            self::assertSame('PURCHASE_RETURN_EXCEEDS_RETURNABLE', $exception->errorCode());
        }
    }

    public function testItMapsPhysicalStockFailureToThePurchasingContract(): void
    {
        [$receipt, $return] = $this->documents('4');
        $returns = $this->createMock(PurchaseReturnRepository::class);
        $returns->expects(self::once())->method('getForUpdate')->willReturn($return);
        $returns->expects(self::once())->method('shippedQuantityByProduct')->willReturn([]);
        $returns->expects(self::never())->method('save');
        $inventory = $this->createStub(InventoryPurchaseReturnShipper::class);
        $inventory->method('ship')->willThrowException(new PurchaseReturnStockUnavailable($this->productId()));

        try {
            $this->handler(
                $returns,
                $this->receiptRepository($receipt),
                $this->corrections([]),
                $inventory,
                $this->createStub(AuthorizationService::class),
                $this->createStub(SecurityAuditTrail::class),
                $this->createStub(OutboxRepository::class),
            )(new ShipPurchaseReturn($return->id(), $this->context()));
            self::fail('Insufficient physical stock must use the Purchasing error contract.');
        } catch (PurchasingRuleViolation $exception) {
            self::assertSame('PURCHASE_RETURN_INSUFFICIENT_STOCK', $exception->errorCode());
        }
    }

    private function handler(
        PurchaseReturnRepository $returns,
        GoodsReceiptRepository $receipts,
        GoodsReceiptCorrectionRepository $corrections,
        InventoryPurchaseReturnShipper $inventory,
        AuthorizationService $authorization,
        SecurityAuditTrail $audit,
        OutboxRepository $outbox,
    ): ShipPurchaseReturnHandler {
        return new ShipPurchaseReturnHandler(
            $returns,
            $receipts,
            $corrections,
            $inventory,
            new ShipPurchaseReturnTransaction(),
            $authorization,
            $this->createStub(OperationalGuard::class),
            $audit,
            $outbox,
            new SymfonyUuidV7Generator(),
            $this->clock,
        );
    }

    private function receiptRepository(GoodsReceipt $receipt): GoodsReceiptRepository
    {
        $repository = $this->createStub(GoodsReceiptRepository::class);
        $repository->method('getForUpdate')->willReturn($receipt);

        return $repository;
    }

    /** @param array<string, Quantity> $differences */
    private function corrections(array $differences): GoodsReceiptCorrectionRepository
    {
        $repository = $this->createStub(GoodsReceiptCorrectionRepository::class);
        $repository->method('postedDifferenceByProduct')->willReturn($differences);

        return $repository;
    }

    /** @return array{GoodsReceipt, PurchaseReturn} */
    private function documents(string $returnQuantity): array
    {
        $receipt = GoodsReceipt::create($this->receiptId(), $this->organizationId(), $this->storeId(), $this->supplierId(), null, GoodsReceiptNumber::fromString('GR-RETURN-SHIP'), null, null, $this->actorId(), $this->clock->now());
        $receipt->addLine(new GoodsReceiptLine($this->receiptLineId(), $receipt->id(), $this->productId(), null, $this->quantity('10'), $this->quantity('1'), $this->quantity('10'), null, $this->money('5'), null));
        $receipt->post($this->actorId(), $this->clock->now());
        $return = PurchaseReturn::create($this->returnId(), $this->organizationId(), $this->storeId(), $this->supplierId(), $receipt->id(), null, 'Damaged delivery', $this->actorId(), $this->clock->now());
        $return->addLine(new PurchaseReturnLine($this->returnLineId(), $return->id(), $this->productId(), $this->quantity($returnQuantity), $receipt->lines()[0]->id()));

        return [$receipt, $return];
    }

    private function context(): ActorContext
    {
        return new ActorContext($this->actorId(), $this->organizationId(), ActorType::User, CorrelationId::fromString('0198e600-0000-7000-8000-000000000009', $this->ids), $this->clock->now());
    }

    private function organizationId(): OrganizationId
    {
        return OrganizationId::fromString('0198e600-0000-7000-8000-000000000001', $this->ids);
    }

    private function storeId(): StoreId
    {
        return StoreId::fromString('0198e600-0000-7000-8000-000000000002', $this->ids);
    }

    private function supplierId(): SupplierId
    {
        return SupplierId::fromString('0198e600-0000-7000-8000-000000000003', $this->ids);
    }

    private function productId(): ProductId
    {
        return ProductId::fromString('0198e600-0000-7000-8000-000000000004', $this->ids);
    }

    private function receiptId(): GoodsReceiptId
    {
        return GoodsReceiptId::fromString('0198e600-0000-7000-8000-000000000005', $this->ids);
    }

    private function receiptLineId(): GoodsReceiptLineId
    {
        return GoodsReceiptLineId::fromString('0198e600-0000-7000-8000-000000000006', $this->ids);
    }

    private function returnId(): PurchaseReturnId
    {
        return PurchaseReturnId::fromString('0198e600-0000-7000-8000-000000000007', $this->ids);
    }

    private function returnLineId(): PurchaseReturnLineId
    {
        return PurchaseReturnLineId::fromString('0198e600-0000-7000-8000-000000000008', $this->ids);
    }

    private function actorId(): ActorId
    {
        return ActorId::fromString('0198e600-0000-7000-8000-000000000010', $this->ids);
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

final class ShipPurchaseReturnTransaction implements TenantTransaction
{
    public function transactional(OrganizationId $organizationId, callable $operation): mixed
    {
        return $operation();
    }
}
