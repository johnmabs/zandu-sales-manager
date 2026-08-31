<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Purchasing\Application;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Modules\Purchasing\Application\CancelPurchaseReturn\CancelPurchaseReturn;
use Zandu\Modules\Purchasing\Application\CancelPurchaseReturn\CancelPurchaseReturnHandler;
use Zandu\Modules\Purchasing\Domain\PurchaseReturn\PurchaseReturn;
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
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\PurchaseReturnId;
use Zandu\SharedKernel\Identity\StoreId;
use Zandu\SharedKernel\Identity\SupplierId;
use Zandu\SharedKernel\Messaging\CorrelationId;
use Zandu\SharedKernel\Messaging\OutboxRepository;
use Zandu\SharedKernel\SecurityAudit\SecurityAction;
use Zandu\SharedKernel\SecurityAudit\SecurityAuditTrail;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\Tests\SharedKernel\Time\FrozenClock;

final class CancelPurchaseReturnTest extends TestCase
{
    private SymfonyUuidFactory $ids;
    private FrozenClock $clock;

    protected function setUp(): void
    {
        $this->ids = new SymfonyUuidFactory();
        $this->clock = new FrozenClock(new DateTimeImmutable('2026-08-31T12:00:00Z'));
    }

    public function testItCancelsADraftWithAuditAndOutbox(): void
    {
        $return = $this->draft();
        $returns = $this->createMock(PurchaseReturnRepository::class);
        $returns->expects(self::once())->method('getForUpdate')->willReturn($return);
        $returns->expects(self::once())->method('save')->with($return);
        $authorization = $this->createMock(AuthorizationService::class);
        $authorization->expects(self::once())->method('authorize')->with($this->context(), PermissionCode::PurchaseReturnCancel, self::anything());
        $audit = $this->createMock(SecurityAuditTrail::class);
        $audit->expects(self::once())->method('recordSuccess')->with($this->context(), SecurityAction::PurchaseReturnCancelled, self::anything(), self::anything(), $this->clock->now());
        $outbox = $this->createMock(OutboxRepository::class);
        $outbox->expects(self::once())->method('append');

        $cancelled = $this->handler($returns, $authorization, $audit, $outbox)(new CancelPurchaseReturn($return->id(), $this->context()));

        self::assertSame(PurchaseReturnStatus::Cancelled, $cancelled->status());
        self::assertEquals($this->clock->now(), $cancelled->cancelledAt());
    }

    public function testReplayDoesNotDuplicateCancellationEffects(): void
    {
        $return = $this->draft();
        $return->cancel($this->actorId(), $this->clock->now());
        $returns = $this->createStub(PurchaseReturnRepository::class);
        $returns->method('getForUpdate')->willReturn($return);
        $audit = $this->createMock(SecurityAuditTrail::class);
        $audit->expects(self::never())->method('recordSuccess');
        $outbox = $this->createMock(OutboxRepository::class);
        $outbox->expects(self::never())->method('append');

        self::assertSame($return, $this->handler(
            $returns,
            $this->createStub(AuthorizationService::class),
            $audit,
            $outbox,
        )(new CancelPurchaseReturn($return->id(), $this->context())));
    }

    public function testItRefusesToCancelAnAlreadyShippedReturn(): void
    {
        $return = $this->draft();
        $return->addLine(new \Zandu\Modules\Purchasing\Domain\PurchaseReturn\PurchaseReturnLine(
            \Zandu\SharedKernel\Identity\PurchaseReturnLineId::fromString('0198e700-0000-7000-8000-000000000010', $this->ids),
            $return->id(),
            \Zandu\SharedKernel\Identity\ProductId::fromString('0198e700-0000-7000-8000-000000000011', $this->ids),
            \Zandu\SharedKernel\Quantity\Quantity::fromString('1', new BrickDecimalFactory()),
            \Zandu\SharedKernel\Identity\GoodsReceiptLineId::fromString('0198e700-0000-7000-8000-000000000012', $this->ids),
        ));
        $return->ship($this->actorId(), $this->clock->now());
        $returns = $this->createStub(PurchaseReturnRepository::class);
        $returns->method('getForUpdate')->willReturn($return);

        $this->expectException(PurchasingRuleViolation::class);
        $this->expectExceptionMessage('immutable');
        $this->handler(
            $returns,
            $this->createStub(AuthorizationService::class),
            $this->createStub(SecurityAuditTrail::class),
            $this->createStub(OutboxRepository::class),
        )(new CancelPurchaseReturn($return->id(), $this->context()));
    }

    private function handler(PurchaseReturnRepository $returns, AuthorizationService $authorization, SecurityAuditTrail $audit, OutboxRepository $outbox): CancelPurchaseReturnHandler
    {
        return new CancelPurchaseReturnHandler(
            $returns,
            new CancelPurchaseReturnTransaction(),
            $authorization,
            $this->createStub(OperationalGuard::class),
            $audit,
            $outbox,
            new SymfonyUuidV7Generator(),
            $this->clock,
        );
    }

    private function draft(): PurchaseReturn
    {
        return PurchaseReturn::create(
            PurchaseReturnId::fromString('0198e700-0000-7000-8000-000000000001', $this->ids),
            $this->organizationId(),
            $this->storeId(),
            SupplierId::fromString('0198e700-0000-7000-8000-000000000002', $this->ids),
            GoodsReceiptId::fromString('0198e700-0000-7000-8000-000000000003', $this->ids),
            null,
            'Damaged delivery',
            $this->actorId(),
            $this->clock->now(),
        );
    }

    private function context(): ActorContext
    {
        return new ActorContext($this->actorId(), $this->organizationId(), ActorType::User, CorrelationId::fromString('0198e700-0000-7000-8000-000000000004', $this->ids), $this->clock->now());
    }

    private function organizationId(): OrganizationId
    {
        return OrganizationId::fromString('0198e700-0000-7000-8000-000000000005', $this->ids);
    }

    private function storeId(): StoreId
    {
        return StoreId::fromString('0198e700-0000-7000-8000-000000000006', $this->ids);
    }

    private function actorId(): ActorId
    {
        return ActorId::fromString('0198e700-0000-7000-8000-000000000007', $this->ids);
    }
}

final class CancelPurchaseReturnTransaction implements TenantTransaction
{
    public function transactional(OrganizationId $organizationId, callable $operation): mixed
    {
        return $operation();
    }
}
