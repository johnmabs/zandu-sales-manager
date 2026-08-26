<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Application;

use Zandu\Modules\CashManagement\Application\Contract\{CashMovementRecorder,CashSalePaymentResult,RecordSalePayment};
use Zandu\Modules\Sales\Domain\Sale;
use Zandu\SharedKernel\Identity\CashSessionId;
use Zandu\SharedKernel\Money\Money;

final readonly class CashPaymentService
{
    public function __construct(private CashMovementRecorder $recorder) {}

    public function record(Sale $sale, CashSessionId $sessionId, Money $amount): CashSalePaymentResult
    {
        if ($sale->currency() !== $amount->currency()->code()) {
            throw new \InvalidArgumentException('Payment currency must match sale currency.');
        }
        return $this->recorder->recordSalePayment(new RecordSalePayment($sale->organizationId(), $sale->storeId(), $sessionId, $sale->id(), $amount, $sale->createdBy()->actorId()));
    }
}
