<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Payments\Application;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\CashManagement\Application\Contract\CashRefundRecorder;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Modules\Payments\Application\{CreateCashPaymentRefund, CreateCashPaymentRefundService};
use Zandu\Modules\Payments\Domain\{Payment, PaymentRefund, PaymentRefundRepository, PaymentRepository, PaymentRuleViolation};
use Zandu\Modules\Sales\Application\Contract\{RefundableReturn, RefundableReturnProvider};
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\{SymfonyUuidFactory, SymfonyUuidV7Generator};
use Zandu\SharedKernel\Context\{ActorContext, ActorType};
use Zandu\SharedKernel\Idempotency\IdempotencyKey;
use Zandu\SharedKernel\Identity\{ActorId, CashSessionId, OrganizationId, PaymentId, ReturnSaleId, SaleId, StoreId};
use Zandu\SharedKernel\Messaging\CorrelationId;
use Zandu\SharedKernel\Messaging\OutboxRepository;
use Zandu\SharedKernel\Money\{Currency, Money};
use Zandu\SharedKernel\SecurityAudit\SecurityAuditTrail;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\Tests\SharedKernel\Time\FrozenClock;

final class CreateCashPaymentRefundServiceTest extends TestCase
{
    public function testItConfirmsARefundAndRecordsExactlyOneCashOut(): void
    {
        $repository = new InMemoryPaymentRefundRepository($this->decimals());
        $cash = $this->createMock(CashRefundRecorder::class);
        $cash->expects(self::once())->method('recordCashRefund')->with(self::callback(
            fn($request): bool => '400' === $request->amount->amount()->toString()
                && $this->storeId()->equals($request->storeId),
        ))->willReturn(false);

        $refund = ($this->service($repository, $cash))($this->command('400', 'key-1'));

        self::assertSame('CONFIRMED', $refund->status()->value);
        self::assertSame('400', $repository->confirmedTotalForPayment($this->organizationId(), $this->paymentId(), Currency::fromCode('XAF'))->amount()->toString());
    }

    public function testSameKeyAndPayloadReturnsTheExistingRefundWithoutAnotherCashMovement(): void
    {
        $repository = new InMemoryPaymentRefundRepository($this->decimals());
        $cash = $this->createMock(CashRefundRecorder::class);
        $cash->expects(self::once())->method('recordCashRefund')->willReturn(false);
        $service = $this->service($repository, $cash);

        $first = $service($this->command('400', 'key-1'));
        $replayed = $service($this->command('400', 'key-1'));

        self::assertTrue($first->id()->equals($replayed->id()));
    }

    public function testSameKeyWithAnotherPayloadIsRejected(): void
    {
        $repository = new InMemoryPaymentRefundRepository($this->decimals());
        $cash = $this->createStub(CashRefundRecorder::class);
        $cash->method('recordCashRefund')->willReturn(false);
        $service = $this->service($repository, $cash);
        $service($this->command('400', 'key-1'));

        try {
            $service($this->command('300', 'key-1'));
            self::fail('A reused key with another payload should fail.');
        } catch (PaymentRuleViolation $exception) {
            self::assertSame('IDEMPOTENCY_CONFLICT', $exception->errorCode());
        }
    }

    public function testCumulativeRefundCannotExceedTheReturnAmount(): void
    {
        $repository = new InMemoryPaymentRefundRepository($this->decimals());
        $cash = $this->createStub(CashRefundRecorder::class);
        $cash->method('recordCashRefund')->willReturn(false);
        $service = $this->service($repository, $cash, paymentAmount: '2000', returnAmount: '1000');
        $service($this->command('700', 'key-1'));

        try {
            $service($this->command('400', 'key-2'));
            self::fail('Refunds beyond the return amount should fail.');
        } catch (PaymentRuleViolation $exception) {
            self::assertSame('REFUND_EXCEEDS_RETURN', $exception->errorCode());
        }
    }

    public function testCumulativeRefundCannotExceedTheConfirmedPayment(): void
    {
        $repository = new InMemoryPaymentRefundRepository($this->decimals());
        $cash = $this->createStub(CashRefundRecorder::class);
        $cash->method('recordCashRefund')->willReturn(false);
        $service = $this->service($repository, $cash, paymentAmount: '800', returnAmount: '1000');
        $service($this->command('600', 'key-1'));

        try {
            $service($this->command('300', 'key-2'));
            self::fail('Refunds beyond the payment amount should fail.');
        } catch (PaymentRuleViolation $exception) {
            self::assertSame('REFUND_EXCEEDS_PAYMENT', $exception->errorCode());
        }
    }

    public function testCashFailureDoesNotPersistAConfirmedRefund(): void
    {
        $repository = new InMemoryPaymentRefundRepository($this->decimals());
        $cash = $this->createStub(CashRefundRecorder::class);
        $cash->method('recordCashRefund')->willThrowException(new \RuntimeException('Injected cash failure.'));

        try {
            ($this->service($repository, $cash))($this->command('400', 'key-1'));
            self::fail('A cash failure should abort the refund.');
        } catch (\RuntimeException $exception) {
            self::assertSame('Injected cash failure.', $exception->getMessage());
        }
        self::assertSame('0', $repository->confirmedTotalForPayment($this->organizationId(), $this->paymentId(), Currency::fromCode('XAF'))->amount()->toString());
    }

    private function service(InMemoryPaymentRefundRepository $refunds, CashRefundRecorder $cash, string $paymentAmount = '1000', string $returnAmount = '1000'): CreateCashPaymentRefundService
    {
        $payment = Payment::createCashSale($this->paymentId(), $this->organizationId(), $this->saleId(), $this->money($paymentAmount), $this->actor()->actorId(), new DateTimeImmutable('2026-08-28T09:00:00Z'));
        $payment->confirm(new DateTimeImmutable('2026-08-28T09:00:00Z'));
        $payments = $this->createStub(PaymentRepository::class);
        $payments->method('getForUpdate')->willReturn($payment);
        $returns = $this->createStub(RefundableReturnProvider::class);
        $returns->method('provide')->willReturn(new RefundableReturn($this->organizationId(), $this->storeId(), $this->saleId(), $this->returnSaleId(), $this->money($returnAmount)));
        $transaction = new class implements TenantTransaction {
            public function transactional(OrganizationId $organizationId, callable $operation): mixed
            {
                return $operation();
            }
        };

        return new CreateCashPaymentRefundService(
            $transaction,
            $payments,
            $refunds,
            $returns,
            $cash,
            $this->createStub(AuthorizationService::class),
            $this->createStub(OperationalGuard::class),
            $this->createStub(SecurityAuditTrail::class),
            $this->createStub(OutboxRepository::class),
            new SymfonyUuidV7Generator(),
            new FrozenClock(new DateTimeImmutable('2026-08-28T12:00:00Z')),
        );
    }

    private function command(string $amount, string $key): CreateCashPaymentRefund
    {
        return new CreateCashPaymentRefund($this->paymentId(), $this->returnSaleId(), $this->cashSessionId(), $this->money($amount), ' Customer return ', IdempotencyKey::fromString($key), $this->actor());
    }

    private function actor(): ActorContext
    {
        return new ActorContext($this->actorId(), $this->organizationId(), ActorType::User, CorrelationId::fromString('019a3500-0000-7000-8000-000000000008', $this->uuids()), new DateTimeImmutable());
    }

    private function organizationId(): OrganizationId
    {
        return OrganizationId::fromString('019a3500-0000-7000-8000-000000000001', $this->uuids());
    }
    private function storeId(): StoreId
    {
        return StoreId::fromString('019a3500-0000-7000-8000-000000000002', $this->uuids());
    }
    private function saleId(): SaleId
    {
        return SaleId::fromString('019a3500-0000-7000-8000-000000000003', $this->uuids());
    }
    private function paymentId(): PaymentId
    {
        return PaymentId::fromString('019a3500-0000-7000-8000-000000000004', $this->uuids());
    }
    private function returnSaleId(): ReturnSaleId
    {
        return ReturnSaleId::fromString('019a3500-0000-7000-8000-000000000005', $this->uuids());
    }
    private function cashSessionId(): CashSessionId
    {
        return CashSessionId::fromString('019a3500-0000-7000-8000-000000000006', $this->uuids());
    }
    private function actorId(): ActorId
    {
        return ActorId::fromString('019a3500-0000-7000-8000-000000000007', $this->uuids());
    }
    private function money(string $amount): Money
    {
        return Money::fromString($amount, Currency::fromCode('XAF'), $this->decimals());
    }
    private function uuids(): SymfonyUuidFactory
    {
        return new SymfonyUuidFactory();
    }
    private function decimals(): BrickDecimalFactory
    {
        return new BrickDecimalFactory();
    }
}

final class InMemoryPaymentRefundRepository implements PaymentRefundRepository
{
    /** @var list<PaymentRefund> */
    private array $refunds = [];

    public function __construct(private readonly BrickDecimalFactory $decimals) {}

    public function add(PaymentRefund $refund): void
    {
        $this->refunds[] = $refund;
    }

    public function findByIdempotencyKey(OrganizationId $organizationId, PaymentId $paymentId, IdempotencyKey $key): ?PaymentRefund
    {
        foreach ($this->refunds as $refund) {
            if ($refund->organizationId()->equals($organizationId) && $refund->paymentId()->equals($paymentId) && $refund->idempotencyKey()->equals($key)) {
                return $refund;
            }
        }

        return null;
    }

    public function confirmedTotalForPayment(OrganizationId $organizationId, PaymentId $paymentId, Currency $currency): Money
    {
        return $this->sum($currency, fn(PaymentRefund $refund): bool => $refund->paymentId()->equals($paymentId));
    }

    public function confirmedTotalForReturn(OrganizationId $organizationId, ReturnSaleId $returnSaleId, Currency $currency): Money
    {
        return $this->sum($currency, fn(PaymentRefund $refund): bool => $refund->returnSaleId()->equals($returnSaleId));
    }

    private function sum(Currency $currency, callable $matches): Money
    {
        $total = Money::fromString('0', $currency, $this->decimals);
        foreach ($this->refunds as $refund) {
            if ($matches($refund)) {
                $total = $total->add($refund->amount());
            }
        }

        return $total;
    }
}
