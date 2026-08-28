<?php

declare(strict_types=1);

namespace Zandu\Modules\CashManagement\Application;

use Zandu\Modules\CashManagement\Application\Contract\{CashRefundRecorder, RecordCashRefund};
use Zandu\Modules\CashManagement\Domain\CashMovement\{CashMovement, CashMovementRepository, CashMovementType};
use Zandu\Modules\CashManagement\Domain\CashSession\{CashSessionRepository, CashSessionStatus};
use Zandu\SharedKernel\Identity\{CashMovementId, IdGenerator};
use Zandu\SharedKernel\Time\Clock;

final readonly class CashRefundMovementRecorder implements CashRefundRecorder
{
    public function __construct(
        private CashSessionRepository $sessions,
        private CashMovementRepository $movements,
        private IdGenerator $ids,
        private Clock $clock,
    ) {}

    public function recordCashRefund(RecordCashRefund $request): bool
    {
        $session = $this->sessions->findForUpdate($request->organizationId, $request->storeId, $request->cashSessionId)
            ?? throw CashRefundRuleViolation::with('CASH_SESSION_NOT_FOUND', 'Cash session not found in the return store.');
        if (CashSessionStatus::Open !== $session->status()) {
            throw CashRefundRuleViolation::with('CASH_SESSION_CLOSED', 'Cash refund requires an open cash session.');
        }
        if (!$session->openingBalance()->currency()->equals($request->amount->currency())) {
            throw CashRefundRuleViolation::with('REFUND_CURRENCY_MISMATCH', 'Cash session and refund currencies must match.');
        }

        $movement = CashMovement::record(
            CashMovementId::generate($this->ids),
            $request->organizationId,
            $request->storeId,
            $request->cashSessionId,
            CashMovementType::Refund,
            $request->amount,
            $request->paymentRefundId->toString(),
            $request->reason,
            $request->actorId,
            null,
            $this->clock->now(),
        );

        return !$this->movements->appendOnce($movement);
    }
}
