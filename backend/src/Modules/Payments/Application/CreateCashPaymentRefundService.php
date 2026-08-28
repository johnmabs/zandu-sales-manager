<?php

declare(strict_types=1);

namespace Zandu\Modules\Payments\Application;

use Zandu\Modules\CashManagement\Application\Contract\{CashRefundRecorder, RecordCashRefund};
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\{OperationalGuard, OperationalMode};
use Zandu\Modules\Payments\Domain\{PaymentRefund, PaymentRefundRepository, PaymentRefundStatus, PaymentRepository, PaymentRuleViolation, PaymentStatus};
use Zandu\Modules\Sales\Application\Contract\RefundableReturnProvider;
use Zandu\SharedKernel\Access\{PermissionCode, ResourceScope};
use Zandu\SharedKernel\Identity\{IdGenerator, OutboxMessageId, PaymentRefundId};
use Zandu\SharedKernel\Messaging\{OutboxMessage, OutboxRepository};
use Zandu\SharedKernel\SecurityAudit\{ResourceReference, SafeAuditMetadata, SecurityAction, SecurityAuditTrail};
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class CreateCashPaymentRefundService
{
    public function __construct(
        private TenantTransaction $transaction,
        private PaymentRepository $payments,
        private PaymentRefundRepository $refunds,
        private RefundableReturnProvider $returns,
        private CashRefundRecorder $cash,
        private AuthorizationService $authorization,
        private OperationalGuard $operationalGuard,
        private SecurityAuditTrail $audit,
        private OutboxRepository $outbox,
        private IdGenerator $ids,
        private Clock $clock,
    ) {}

    public function __invoke(CreateCashPaymentRefund $command): PaymentRefund
    {
        return $this->transaction->transactional($command->actor->organizationId(), function () use ($command): PaymentRefund {
            $payment = $this->payments->getForUpdate($command->actor->organizationId(), $command->paymentId);
            $payloadHash = $this->payloadHash($command);
            $existing = $this->refunds->findByIdempotencyKey($command->actor->organizationId(), $payment->id(), $command->idempotencyKey);
            if (null !== $existing) {
                if (!$existing->matchesPayload($payloadHash)) {
                    throw PaymentRuleViolation::with('IDEMPOTENCY_CONFLICT', 'Idempotency key was already used with a different refund payload.');
                }

                return $existing;
            }
            if (PaymentStatus::Confirmed !== $payment->status() || 'CASH' !== $payment->method()) {
                throw PaymentRuleViolation::with('PAYMENT_NOT_REFUNDABLE', 'Only a confirmed cash payment can be refunded.');
            }
            if ($command->amount->amount()->isNegative() || $command->amount->amount()->isZero()) {
                throw PaymentRuleViolation::with('REFUND_AMOUNT_INVALID', 'Refund amount must be greater than zero.');
            }

            $return = $this->returns->provide($command->actor->organizationId(), $command->returnSaleId);
            $this->authorization->authorize(
                $command->actor,
                PermissionCode::PaymentRefundCreate,
                ResourceScope::store($command->actor->organizationId(), $return->storeId),
            );
            $this->operationalGuard->assertStore($command->actor, $return->storeId, OperationalMode::Standard);
            if (!$return->saleId->equals($payment->targetReference())) {
                throw PaymentRuleViolation::with('REFUND_RETURN_PAYMENT_MISMATCH', 'Return and payment must belong to the same sale.');
            }
            if (!$command->amount->currency()->equals($payment->amount()->currency())
                || !$command->amount->currency()->equals($return->refundableAmount->currency())) {
                throw PaymentRuleViolation::with('REFUND_CURRENCY_MISMATCH', 'Payment, return and refund currencies must match.');
            }

            $paymentTotal = $this->refunds->confirmedTotalForPayment($command->actor->organizationId(), $payment->id(), $command->amount->currency());
            if ($paymentTotal->add($command->amount)->compareTo($payment->amount()) > 0) {
                throw PaymentRuleViolation::with('REFUND_EXCEEDS_PAYMENT', 'Cumulative refunds cannot exceed the confirmed payment amount.');
            }
            $returnTotal = $this->refunds->confirmedTotalForReturn($command->actor->organizationId(), $return->returnSaleId, $command->amount->currency());
            if ($returnTotal->add($command->amount)->compareTo($return->refundableAmount) > 0) {
                throw PaymentRuleViolation::with('REFUND_EXCEEDS_RETURN', 'Cumulative refunds cannot exceed the return refundable amount.');
            }

            $now = $this->clock->now();
            $refund = PaymentRefund::create(
                PaymentRefundId::generate($this->ids),
                $command->actor->organizationId(),
                $payment->id(),
                $return->returnSaleId,
                $command->cashSessionId,
                $command->amount,
                $command->reason,
                $command->idempotencyKey,
                $payloadHash,
                $command->actor->actorId(),
                $now,
            );
            $replayedCash = $this->cash->recordCashRefund(new RecordCashRefund(
                $command->actor->organizationId(),
                $return->storeId,
                $command->cashSessionId,
                $refund->id(),
                $refund->amount(),
                $refund->reason(),
                $command->actor->actorId(),
            ));
            if ($replayedCash) {
                throw PaymentRuleViolation::with('REFUND_CASH_MOVEMENT_CONFLICT', 'Cash refund movement already exists before its payment refund.');
            }

            $refund->confirm($now);
            $this->refunds->add($refund);
            $this->audit->recordSuccess(
                $command->actor,
                SecurityAction::PaymentRefundConfirmed,
                ResourceReference::for('payment_refund', $refund->id()),
                SafeAuditMetadata::fromArray(['paymentId' => $payment->id()->toString(), 'returnSaleId' => $return->returnSaleId->toString()]),
                $now,
            );
            $this->outbox->append(new OutboxMessage(
                OutboxMessageId::generate($this->ids),
                $command->actor->organizationId(),
                'payments.payment_refund_confirmed.v1',
                [
                    'paymentRefundId' => $refund->id()->toString(),
                    'paymentId' => $payment->id()->toString(),
                    'returnSaleId' => $return->returnSaleId->toString(),
                    'cashSessionId' => $command->cashSessionId->toString(),
                    'amount' => $refund->amount()->amount()->toString(),
                    'currency' => $refund->amount()->currency()->code(),
                ],
                $command->actor->correlationId(),
                $command->actor->causationId(),
                $now,
            ));

            return $refund;
        });
    }

    private function payloadHash(CreateCashPaymentRefund $command): string
    {
        return hash('sha256', json_encode([
            'returnSaleId' => $command->returnSaleId->toString(),
            'cashSessionId' => $command->cashSessionId->toString(),
            'amount' => $command->amount->amount()->toString(),
            'currency' => $command->amount->currency()->code(),
            'reason' => null === $command->reason || '' === trim($command->reason) ? null : trim($command->reason),
        ], JSON_THROW_ON_ERROR));
    }
}
