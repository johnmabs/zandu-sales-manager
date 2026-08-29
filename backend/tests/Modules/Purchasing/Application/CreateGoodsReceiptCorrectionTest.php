<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Purchasing\Application;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Modules\Purchasing\Application\CreateGoodsReceiptCorrection\{CreateGoodsReceiptCorrection, CreateGoodsReceiptCorrectionHandler, GoodsReceiptCorrectionInput};
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\{GoodsReceipt, GoodsReceiptLine, GoodsReceiptNumber, GoodsReceiptRepository};
use Zandu\Modules\Purchasing\Domain\GoodsReceiptCorrection\{GoodsReceiptCorrection, GoodsReceiptCorrectionRepository};
use Zandu\Modules\Purchasing\Domain\PurchasingRuleViolation;
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Context\{ActorContext, ActorType};
use Zandu\SharedKernel\Identity\{ActorId, GoodsReceiptCorrectionId, GoodsReceiptId, GoodsReceiptLineId, IdGenerator, OrganizationId, ProductId, StoreId, SupplierId, Uuid};
use Zandu\SharedKernel\Messaging\CorrelationId;
use Zandu\SharedKernel\Money\{Currency, Money};
use Zandu\SharedKernel\Quantity\Quantity;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\Tests\SharedKernel\Time\FrozenClock;

final class CreateGoodsReceiptCorrectionTest extends TestCase
{
    private SymfonyUuidFactory $ids;
    private BrickDecimalFactory $decimals;
    private FrozenClock $clock;

    protected function setUp(): void
    {
        $this->ids = new SymfonyUuidFactory();
        $this->decimals = new BrickDecimalFactory();
        $this->clock = new FrozenClock(new DateTimeImmutable('2026-08-29T16:00:00Z'));
    }

    public function testItSnapshotsTheCurrentEffectiveQuantityFromPostedCorrections(): void
    {
        $receipt = $this->receipt(true);
        $receipts = $this->createStub(GoodsReceiptRepository::class);
        $receipts->method('getForUpdate')->willReturn($receipt);
        $corrections = $this->createMock(GoodsReceiptCorrectionRepository::class);
        $corrections->method('postedDifferenceByProduct')->willReturn([$this->productId()->toString() => $this->quantity('-2')]);
        $corrections->expects(self::once())->method('save')->with(self::isInstanceOf(GoodsReceiptCorrection::class));
        $authorization = $this->createMock(AuthorizationService::class);
        $authorization->expects(self::once())->method('authorize')->with($this->context(), PermissionCode::PurchasingReceiptCorrect, self::anything());

        $correction = $this->handler($receipts, $corrections, $authorization)(new CreateGoodsReceiptCorrection(
            $receipt->id(),
            'Recount after damaged package',
            [new GoodsReceiptCorrectionInput($this->productId(), $this->quantity('7'))],
            $this->context(),
        ));

        self::assertSame('10', $correction->lines()[0]->originalReceivedQuantity()->toString());
        self::assertSame('8', $correction->lines()[0]->currentEffectiveQuantity()->toString());
        self::assertSame('-1', $correction->lines()[0]->difference()->toString());
    }

    public function testItRejectsCorrectionOfAnUnpostedReceipt(): void
    {
        $receipts = $this->createStub(GoodsReceiptRepository::class);
        $receipts->method('getForUpdate')->willReturn($this->receipt(false));
        $corrections = $this->createMock(GoodsReceiptCorrectionRepository::class);
        $corrections->expects(self::never())->method('save');

        try {
            $this->handler($receipts, $corrections, $this->createStub(AuthorizationService::class))(new CreateGoodsReceiptCorrection($this->receiptId(), 'Invalid correction', [new GoodsReceiptCorrectionInput($this->productId(), $this->quantity('7'))], $this->context()));
            self::fail('A draft receipt cannot be corrected.');
        } catch (PurchasingRuleViolation $exception) {
            self::assertSame('GOODS_RECEIPT_CORRECTION_RECEIPT_NOT_POSTED', $exception->errorCode());
        }
    }

    private function handler(GoodsReceiptRepository $receipts, GoodsReceiptCorrectionRepository $corrections, AuthorizationService $authorization): CreateGoodsReceiptCorrectionHandler
    {
        return new CreateGoodsReceiptCorrectionHandler($receipts, $corrections, new CorrectionIdGenerator($this->ids->fromString('0198df10-0000-7000-8000-000000000008')), $this->clock, new CorrectionTransaction(), $authorization, $this->createStub(OperationalGuard::class));
    }

    private function receipt(bool $posted): GoodsReceipt
    {
        $receipt = GoodsReceipt::create($this->receiptId(), $this->organizationId(), $this->storeId(), $this->supplierId(), null, GoodsReceiptNumber::fromString('GR-CORRECT'), null, null, $this->actorId(), $this->clock->now());
        $receipt->addLine(new GoodsReceiptLine(GoodsReceiptLineId::fromString('0198df10-0000-7000-8000-000000000007', $this->ids), $receipt->id(), $this->productId(), null, $this->quantity('10'), $this->quantity('1'), $this->quantity('10'), null, Money::fromString('5', Currency::fromCode('XAF'), $this->decimals), null));
        if ($posted) {
            $receipt->post($this->actorId(), $this->clock->now());
        }
        return $receipt;
    }

    private function context(): ActorContext
    {
        return new ActorContext($this->actorId(), $this->organizationId(), ActorType::User, CorrelationId::fromString('0198df10-0000-7000-8000-000000000006', $this->ids), $this->clock->now());
    }
    private function organizationId(): OrganizationId
    {
        return OrganizationId::fromString('0198df10-0000-7000-8000-000000000001', $this->ids);
    }
    private function storeId(): StoreId
    {
        return StoreId::fromString('0198df10-0000-7000-8000-000000000002', $this->ids);
    }
    private function supplierId(): SupplierId
    {
        return SupplierId::fromString('0198df10-0000-7000-8000-000000000003', $this->ids);
    }
    private function productId(): ProductId
    {
        return ProductId::fromString('0198df10-0000-7000-8000-000000000004', $this->ids);
    }
    private function receiptId(): GoodsReceiptId
    {
        return GoodsReceiptId::fromString('0198df10-0000-7000-8000-000000000005', $this->ids);
    }
    private function actorId(): ActorId
    {
        return ActorId::fromString('0198df10-0000-7000-8000-000000000009', $this->ids);
    }
    private function quantity(string $value): Quantity
    {
        return Quantity::fromString($value, $this->decimals);
    }
}

final readonly class CorrectionIdGenerator implements IdGenerator
{
    public function __construct(private Uuid $id) {}
    public function generate(): Uuid
    {
        return $this->id;
    }
}

final class CorrectionTransaction implements TenantTransaction
{
    public function transactional(OrganizationId $organizationId, callable $operation): mixed
    {
        return $operation();
    }
}
