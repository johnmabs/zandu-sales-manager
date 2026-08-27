<?php

declare(strict_types=1);

namespace Zandu\Modules\Payments\Application;

use Zandu\Modules\Payments\Domain\{Payment,PaymentRepository};
use Zandu\Modules\Sales\Application\Contract\PaymentRecorder;
use Zandu\SharedKernel\Identity\{ActorId,IdGenerator,OrganizationId,PaymentId,SaleId};
use Zandu\SharedKernel\Money\Money;
use Zandu\SharedKernel\Time\Clock;

final readonly class CashSalePaymentRecorder implements PaymentRecorder
{
    public function __construct(
        private PaymentRepository $payments,
        private IdGenerator $ids,
        private Clock $clock,
    ) {}

    public function recordCashSale(OrganizationId $organizationId, SaleId $saleId, Money $amount, ActorId $actorId): PaymentId
    {
        $now = $this->clock->now();
        $payment = Payment::createCashSale(PaymentId::generate($this->ids), $organizationId, $saleId, $amount, $actorId, $now);
        $payment->confirm($now);
        if ($this->payments->addConfirmedOnce($payment)) {
            return $payment->id();
        }

        $existing = $this->payments->findConfirmedCashSale($organizationId, $saleId)
            ?? throw new \LogicException('Confirmed cash sale payment could not be reloaded.');
        if (!$existing->amount()->equals($amount)) {
            throw new \LogicException('Existing cash sale payment does not match sale total.');
        }

        return $existing->id();
    }
}
