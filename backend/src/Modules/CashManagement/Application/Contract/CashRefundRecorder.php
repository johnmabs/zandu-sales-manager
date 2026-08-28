<?php

declare(strict_types=1);

namespace Zandu\Modules\CashManagement\Application\Contract;

interface CashRefundRecorder
{
    /** Returns true when the movement already existed. */
    public function recordCashRefund(RecordCashRefund $request): bool;
}
