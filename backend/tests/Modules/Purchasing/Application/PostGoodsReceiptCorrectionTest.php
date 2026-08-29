<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Purchasing\Application;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Inventory\Application\Contract\{ApplyGoodsReceiptCorrection, InventoryGoodsReceiptCorrector};
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Modules\Purchasing\Application\PostGoodsReceiptCorrection\{PostGoodsReceiptCorrection, PostGoodsReceiptCorrectionHandler};
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\{GoodsReceipt, GoodsReceiptLine, GoodsReceiptNumber, GoodsReceiptRepository};
use Zandu\Modules\Purchasing\Domain\GoodsReceiptCorrection\{GoodsReceiptCorrection, GoodsReceiptCorrectionLine, GoodsReceiptCorrectionRepository, GoodsReceiptCorrectionStatus};
use Zandu\Modules\Purchasing\Domain\PurchaseOrder\PurchaseOrderRepository;
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\{SymfonyUuidFactory, SymfonyUuidV7Generator};
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Context\{ActorContext, ActorType};
use Zandu\SharedKernel\Identity\{ActorId, GoodsReceiptCorrectionId, GoodsReceiptId, GoodsReceiptLineId, OrganizationId, ProductId, StoreId, SupplierId};
use Zandu\SharedKernel\Messaging\{CorrelationId, OutboxRepository};
use Zandu\SharedKernel\Money\{Currency, Money};
use Zandu\SharedKernel\Quantity\Quantity;
use Zandu\SharedKernel\SecurityAudit\{SecurityAction, SecurityAuditTrail};
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\Tests\SharedKernel\Time\FrozenClock;

final class PostGoodsReceiptCorrectionTest extends TestCase
{
    private SymfonyUuidFactory $ids;
    private BrickDecimalFactory $decimals;
    private FrozenClock $clock;

    protected function setUp(): void
    {
        $this->ids = new SymfonyUuidFactory();
        $this->decimals = new BrickDecimalFactory();
        $this->clock = new FrozenClock(new DateTimeImmutable('2026-08-29T18:00:00Z'));
    }

    public function testItPostsARevalidatedCorrectionWithInventoryAuditAndOutbox(): void
    {
        [$receipt, $correction] = $this->documents();
        $corrections = $this->createMock(GoodsReceiptCorrectionRepository::class);
        $corrections->method('getForUpdate')->willReturn($correction);
        $corrections->method('postedDifferenceByProduct')->willReturn([]);
        $corrections->expects(self::once())->method('save')->with($correction);
        $receipts = $this->createStub(GoodsReceiptRepository::class);
        $receipts->method('getForUpdate')->willReturn($receipt);
        $inventory = $this->createMock(InventoryGoodsReceiptCorrector::class);
        $inventory->expects(self::once())->method('apply')->with(self::callback(static fn(ApplyGoodsReceiptCorrection $request): bool => '-2' === $request->items[0]['difference']->toString() && '5' === $request->items[0]['incomingUnitCost']->amount()->toString()))->willReturn(1);
        $authorization = $this->createMock(AuthorizationService::class);
        $authorization->expects(self::once())->method('authorize')->with($this->context(), PermissionCode::PurchasingReceiptCorrect, self::anything());
        $audit = $this->createMock(SecurityAuditTrail::class);
        $audit->expects(self::once())->method('recordSuccess')->with($this->context(), SecurityAction::GoodsReceiptCorrected, self::anything(), self::anything(), $this->clock->now());
        $outbox = $this->createMock(OutboxRepository::class);
        $outbox->expects(self::once())->method('append');

        $posted = $this->handler($corrections, $receipts, $inventory, $authorization, $audit, $outbox)(new PostGoodsReceiptCorrection($correction->id(), $this->context(), 'correction-command'));

        self::assertSame(GoodsReceiptCorrectionStatus::Posted, $posted->status());
        self::assertEquals($this->clock->now(), $posted->postedAt());
    }

    public function testReplayDoesNotDuplicateCorrectionEffects(): void
    {
        [$receipt, $correction] = $this->documents();
        $correction->post($this->actorId(), $this->clock->now());
        $corrections = $this->createStub(GoodsReceiptCorrectionRepository::class);
        $corrections->method('getForUpdate')->willReturn($correction);
        $receipts = $this->createStub(GoodsReceiptRepository::class);
        $receipts->method('getForUpdate')->willReturn($receipt);
        $inventory = $this->createMock(InventoryGoodsReceiptCorrector::class);
        $inventory->expects(self::never())->method('apply');

        $replayed = $this->handler($corrections, $receipts, $inventory, $this->createStub(AuthorizationService::class), $this->createStub(SecurityAuditTrail::class), $this->createStub(OutboxRepository::class))(new PostGoodsReceiptCorrection($correction->id(), $this->context()));
        self::assertSame($correction, $replayed);
    }

    private function handler(GoodsReceiptCorrectionRepository $corrections, GoodsReceiptRepository $receipts, InventoryGoodsReceiptCorrector $inventory, AuthorizationService $authorization, SecurityAuditTrail $audit, OutboxRepository $outbox): PostGoodsReceiptCorrectionHandler
    {
        return new PostGoodsReceiptCorrectionHandler($corrections, $receipts, $this->createStub(PurchaseOrderRepository::class), $inventory, new PostCorrectionTransaction(), $authorization, $this->createStub(OperationalGuard::class), $audit, $outbox, new SymfonyUuidV7Generator(), $this->clock);
    }

    /** @return array{GoodsReceipt, GoodsReceiptCorrection} */
    private function documents(): array
    {
        $receipt = GoodsReceipt::create($this->receiptId(), $this->organizationId(), $this->storeId(), $this->supplierId(), null, GoodsReceiptNumber::fromString('GR-CORRECTION'), null, null, $this->actorId(), $this->clock->now());
        $receipt->addLine(new GoodsReceiptLine(GoodsReceiptLineId::fromString('0198e100-0000-7000-8000-000000000007', $this->ids), $receipt->id(), $this->productId(), null, $this->quantity('10'), $this->quantity('1'), $this->quantity('10'), null, $this->money('5'), null));
        $receipt->post($this->actorId(), $this->clock->now());
        $correction = GoodsReceiptCorrection::create(GoodsReceiptCorrectionId::fromString('0198e100-0000-7000-8000-000000000008', $this->ids), $this->organizationId(), $receipt->id(), 'Damaged units', $this->actorId(), $this->clock->now());
        $correction->addLine(new GoodsReceiptCorrectionLine($correction->id(), $this->productId(), $this->quantity('10'), $this->quantity('10'), $this->quantity('8')));
        return [$receipt, $correction];
    }

    private function context(): ActorContext
    {
        return new ActorContext($this->actorId(), $this->organizationId(), ActorType::User, CorrelationId::fromString('0198e100-0000-7000-8000-000000000006', $this->ids), $this->clock->now());
    }
    private function organizationId(): OrganizationId
    {
        return OrganizationId::fromString('0198e100-0000-7000-8000-000000000001', $this->ids);
    }
    private function storeId(): StoreId
    {
        return StoreId::fromString('0198e100-0000-7000-8000-000000000002', $this->ids);
    }
    private function supplierId(): SupplierId
    {
        return SupplierId::fromString('0198e100-0000-7000-8000-000000000003', $this->ids);
    }
    private function productId(): ProductId
    {
        return ProductId::fromString('0198e100-0000-7000-8000-000000000004', $this->ids);
    }
    private function receiptId(): GoodsReceiptId
    {
        return GoodsReceiptId::fromString('0198e100-0000-7000-8000-000000000005', $this->ids);
    }
    private function actorId(): ActorId
    {
        return ActorId::fromString('0198e100-0000-7000-8000-000000000009', $this->ids);
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

final class PostCorrectionTransaction implements TenantTransaction
{
    public function transactional(OrganizationId $organizationId, callable $operation): mixed
    {
        return $operation();
    }
}
