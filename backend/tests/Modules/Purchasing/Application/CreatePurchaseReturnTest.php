<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Purchasing\Application;

use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Modules\Purchasing\Application\CreatePurchaseReturn\CreatePurchaseReturn;
use Zandu\Modules\Purchasing\Application\CreatePurchaseReturn\CreatePurchaseReturnHandler;
use Zandu\Modules\Purchasing\Application\CreatePurchaseReturn\PurchaseReturnInput;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\GoodsReceipt;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\GoodsReceiptLine;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\GoodsReceiptNumber;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\GoodsReceiptRepository;
use Zandu\Modules\Purchasing\Domain\GoodsReceiptCorrection\GoodsReceiptCorrectionRepository;
use Zandu\Modules\Purchasing\Domain\PurchaseReturn\PurchaseReturn;
use Zandu\Modules\Purchasing\Domain\PurchaseReturn\PurchaseReturnRepository;
use Zandu\Modules\Purchasing\Domain\PurchaseReturn\PurchaseReturnStatus;
use Zandu\Modules\Purchasing\Domain\PurchasingRuleViolation;
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

final class CreatePurchaseReturnTest extends TestCase
{
    private SymfonyUuidFactory $ids;
    private BrickDecimalFactory $decimals;
    private FrozenClock $clock;
    private GoodsReceipt $receipt;
    private GoodsReceiptRepository&MockObject $receipts;
    private GoodsReceiptCorrectionRepository&MockObject $corrections;
    private PurchaseReturnRepository&MockObject $returns;
    private AuthorizationService&MockObject $authorization;

    protected function setUp(): void
    {
        $this->ids = new SymfonyUuidFactory();
        $this->decimals = new BrickDecimalFactory();
        $this->clock = new FrozenClock(new DateTimeImmutable('2026-08-31T09:00:00Z'));
        $this->receipt = $this->postedReceipt();
        $this->receipts = $this->createMock(GoodsReceiptRepository::class);
        $this->receipts->expects(self::once())->method('getForUpdate')->willReturn($this->receipt);
        $this->corrections = $this->createMock(GoodsReceiptCorrectionRepository::class);
        $this->returns = $this->createMock(PurchaseReturnRepository::class);
        $this->authorization = $this->createMock(AuthorizationService::class);
        $this->authorization->expects(self::once())->method('authorize')->with(
            $this->context(),
            PermissionCode::PurchaseReturnCreate,
            self::callback(static fn($scope): bool => $scope->storeId?->toString() === self::STORE),
        );
    }

    public function testItCreatesALinkedDraftFromTheEffectiveReturnableBalance(): void
    {
        $this->corrections->expects(self::once())->method('postedDifferenceByProduct')->willReturn([
            $this->productId()->toString() => $this->quantity('-2'),
        ]);
        $this->returns->method('shippedQuantityByProduct')->willReturn([
            $this->productId()->toString() => $this->quantity('3'),
        ]);
        $this->returns->expects(self::once())->method('save')->with(self::isInstanceOf(PurchaseReturn::class));
        $return = $this->handler()(new CreatePurchaseReturn(
            $this->storeId(),
            $this->receipt->id(),
            'Damaged packages',
            [new PurchaseReturnInput($this->receiptLineId(), $this->quantity('5'))],
            $this->context(),
        ));

        self::assertSame(PurchaseReturnStatus::Draft, $return->status());
        self::assertTrue($return->supplierId()->equals($this->supplierId()));
        self::assertTrue($return->purchaseOrderId()?->equals($this->purchaseOrderId()));
        self::assertSame('5', $return->lines()[0]->baseQuantity()->toString());
        self::assertTrue($return->lines()[0]->goodsReceiptLineId()?->equals($this->receiptLineId()));
    }

    public function testItRejectsAQuantityAboveTheCorrectedAndAlreadyReturnedBalance(): void
    {
        $this->corrections->expects(self::once())->method('postedDifferenceByProduct')->willReturn([
            $this->productId()->toString() => $this->quantity('-2'),
        ]);
        $this->returns->method('shippedQuantityByProduct')->willReturn([
            $this->productId()->toString() => $this->quantity('3'),
        ]);
        $this->returns->expects(self::never())->method('save');

        try {
            $this->handler()(new CreatePurchaseReturn(
                $this->storeId(),
                $this->receipt->id(),
                'Too many packages',
                [new PurchaseReturnInput($this->receiptLineId(), $this->quantity('6'))],
                $this->context(),
            ));
            self::fail('The return must not exceed the effective receipt balance.');
        } catch (PurchasingRuleViolation $exception) {
            self::assertSame('PURCHASE_RETURN_EXCEEDS_RETURNABLE', $exception->errorCode());
        }
    }

    public function testItRejectsAStoreDifferentFromTheSourceReceipt(): void
    {
        $this->corrections->expects(self::never())->method('postedDifferenceByProduct');
        $this->returns->expects(self::never())->method('save');

        try {
            $this->handler()(new CreatePurchaseReturn(
                StoreId::fromString('0198e400-0000-7000-8000-000000000099', $this->ids),
                $this->receipt->id(),
                'Wrong store',
                [new PurchaseReturnInput($this->receiptLineId(), $this->quantity('1'))],
                $this->context(),
            ));
            self::fail('A return cannot use a store other than its source receipt.');
        } catch (PurchasingRuleViolation $exception) {
            self::assertSame('PURCHASE_RETURN_STORE_MISMATCH', $exception->errorCode());
        }
    }

    private function handler(): CreatePurchaseReturnHandler
    {
        return new CreatePurchaseReturnHandler(
            $this->receipts,
            $this->corrections,
            $this->returns,
            new PurchaseReturnIdSequence($this->ids),
            $this->clock,
            new PurchaseReturnTransaction(),
            $this->authorization,
            $this->createStub(OperationalGuard::class),
        );
    }

    private function postedReceipt(): GoodsReceipt
    {
        $receipt = GoodsReceipt::create(
            $this->receiptId(),
            $this->organizationId(),
            $this->storeId(),
            $this->supplierId(),
            $this->purchaseOrderId(),
            GoodsReceiptNumber::fromString('GR-RETURN-001'),
            null,
            null,
            $this->actorId(),
            $this->clock->now(),
        );
        $receipt->addLine(new GoodsReceiptLine(
            $this->receiptLineId(),
            $receipt->id(),
            $this->productId(),
            null,
            $this->quantity('10'),
            $this->quantity('1'),
            $this->quantity('10'),
            null,
            Money::fromString('4', Currency::fromCode('XAF'), $this->decimals),
            $this->purchaseOrderLineId(),
        ));
        $receipt->post($this->actorId(), $this->clock->now());

        return $receipt;
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

    private function purchaseOrderId(): PurchaseOrderId
    {
        return PurchaseOrderId::fromString(self::PURCHASE_ORDER, $this->ids);
    }

    private function purchaseOrderLineId(): PurchaseOrderLineId
    {
        return PurchaseOrderLineId::fromString(self::PURCHASE_ORDER_LINE, $this->ids);
    }

    private function receiptId(): GoodsReceiptId
    {
        return GoodsReceiptId::fromString(self::RECEIPT, $this->ids);
    }

    private function receiptLineId(): GoodsReceiptLineId
    {
        return GoodsReceiptLineId::fromString(self::RECEIPT_LINE, $this->ids);
    }

    private function actorId(): ActorId
    {
        return ActorId::fromString(self::ACTOR, $this->ids);
    }

    private function quantity(string $value): Quantity
    {
        return Quantity::fromString($value, $this->decimals);
    }

    private const ORGANIZATION = '0198e400-0000-7000-8000-000000000001';
    private const STORE = '0198e400-0000-7000-8000-000000000002';
    private const SUPPLIER = '0198e400-0000-7000-8000-000000000003';
    private const PRODUCT = '0198e400-0000-7000-8000-000000000004';
    private const PURCHASE_ORDER = '0198e400-0000-7000-8000-000000000005';
    private const PURCHASE_ORDER_LINE = '0198e400-0000-7000-8000-000000000015';
    private const RECEIPT = '0198e400-0000-7000-8000-000000000006';
    private const RECEIPT_LINE = '0198e400-0000-7000-8000-000000000007';
    private const ACTOR = '0198e400-0000-7000-8000-000000000008';
    private const CORRELATION = '0198e400-0000-7000-8000-000000000009';
}

final class PurchaseReturnIdSequence implements IdGenerator
{
    private int $sequence = 10;

    public function __construct(private readonly SymfonyUuidFactory $ids) {}

    public function generate(): Uuid
    {
        return $this->ids->fromString(sprintf('0198e400-0000-7000-8000-%012d', $this->sequence++));
    }
}

final class PurchaseReturnTransaction implements TenantTransaction
{
    public function transactional(OrganizationId $organizationId, callable $operation): mixed
    {
        return $operation();
    }
}
