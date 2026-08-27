<?php

declare(strict_types=1);

namespace Zandu\Modules\CashManagement\Application;

use LogicException;
use Zandu\Modules\CashManagement\Application\Contract\{CashMovementRecorder,CashSalePaymentResult,RecordSalePayment};
use Zandu\Modules\CashManagement\Domain\CashMovement\{CashMovement,CashMovementRepository,CashMovementType};
use Zandu\Modules\CashManagement\Domain\CashSession\{CashSessionRepository,CashSessionStatus};
use Zandu\SharedKernel\Identity\{CashMovementId,IdGenerator};
use Zandu\SharedKernel\Time\Clock;

final readonly class SalePaymentCashMovementRecorder implements CashMovementRecorder
{
    public function __construct(
        private CashSessionRepository $sessions,
        private CashMovementRepository $movements,
        private IdGenerator $ids,
        private Clock $clock,
    ) {}

    public function recordSalePayment(RecordSalePayment $request): CashSalePaymentResult
    {
        $session = $this->sessions->findForUpdate($request->organizationId, $request->storeId, $request->cashSessionId)
            ?? throw new LogicException('Cash session not found.');
        if (CashSessionStatus::Open !== $session->status()) {
            throw new LogicException('Cash session is closed.');
        }
        if (!$session->openingBalance()->currency()->equals($request->amount->currency())) {
            throw new LogicException('Cash session and payment currencies must match.');
        }

        $movement = CashMovement::record(
            CashMovementId::generate($this->ids),
            $request->organizationId,
            $request->storeId,
            $request->cashSessionId,
            CashMovementType::SalePayment,
            $request->amount,
            $request->saleId->toString(),
            null,
            $request->actorId,
            null,
            $this->clock->now(),
        );

        return new CashSalePaymentResult($request->saleId, !$this->movements->appendOnce($movement));
    }
}
