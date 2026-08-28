<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\CashManagement\Application;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\CashManagement\Application\CashRefundMovementRecorder;
use Zandu\Modules\CashManagement\Application\Contract\RecordCashRefund;
use Zandu\Modules\CashManagement\Domain\CashMovement\{CashMovement, CashMovementRepository, CashMovementType};
use Zandu\Modules\CashManagement\Domain\CashSession\{CashSession, CashSessionRepository};
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\{SymfonyUuidFactory, SymfonyUuidV7Generator};
use Zandu\SharedKernel\Identity\{ActorId, CashRegisterId, CashSessionId, OrganizationId, PaymentRefundId, StoreId};
use Zandu\SharedKernel\Money\{Currency, Money};
use Zandu\Tests\SharedKernel\Time\FrozenClock;

final class CashRefundMovementRecorderTest extends TestCase
{
    public function testItAppendsAnOutgoingRefundOnTheOpenSession(): void
    {
        $session = CashSession::open($this->sessionId(), $this->organizationId(), $this->storeId(), $this->registerId(), $this->actorId(), $this->money('100'), new DateTimeImmutable());
        $sessions = $this->createStub(CashSessionRepository::class);
        $sessions->method('findForUpdate')->willReturn($session);
        $movements = $this->createMock(CashMovementRepository::class);
        $movements->expects(self::once())->method('appendOnce')->with(self::callback(
            static fn(CashMovement $movement): bool => CashMovementType::Refund === $movement->type() && !$movement->type()->isIn(),
        ))->willReturn(true);
        $recorder = new CashRefundMovementRecorder($sessions, $movements, new SymfonyUuidV7Generator(), new FrozenClock(new DateTimeImmutable('2026-08-28T12:00:00Z')));

        $replayed = $recorder->recordCashRefund(new RecordCashRefund($this->organizationId(), $this->storeId(), $this->sessionId(), $this->refundId(), $this->money('25'), null, $this->actorId()));

        self::assertFalse($replayed);
    }

    private function organizationId(): OrganizationId
    {
        return OrganizationId::fromString('019a3600-0000-7000-8000-000000000001', $this->uuids());
    }
    private function storeId(): StoreId
    {
        return StoreId::fromString('019a3600-0000-7000-8000-000000000002', $this->uuids());
    }
    private function sessionId(): CashSessionId
    {
        return CashSessionId::fromString('019a3600-0000-7000-8000-000000000003', $this->uuids());
    }
    private function registerId(): CashRegisterId
    {
        return CashRegisterId::fromString('019a3600-0000-7000-8000-000000000004', $this->uuids());
    }
    private function actorId(): ActorId
    {
        return ActorId::fromString('019a3600-0000-7000-8000-000000000005', $this->uuids());
    }
    private function refundId(): PaymentRefundId
    {
        return PaymentRefundId::fromString('019a3600-0000-7000-8000-000000000006', $this->uuids());
    }
    private function money(string $amount): Money
    {
        return Money::fromString($amount, Currency::fromCode('XAF'), new BrickDecimalFactory());
    }
    private function uuids(): SymfonyUuidFactory
    {
        return new SymfonyUuidFactory();
    }
}
