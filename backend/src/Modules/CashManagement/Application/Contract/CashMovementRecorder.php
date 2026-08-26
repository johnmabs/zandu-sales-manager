<?php

declare(strict_types=1);

namespace Zandu\Modules\CashManagement\Application\Contract;

interface CashMovementRecorder
{
    public function recordSalePayment(RecordSalePayment $request): CashSalePaymentResult;
}
