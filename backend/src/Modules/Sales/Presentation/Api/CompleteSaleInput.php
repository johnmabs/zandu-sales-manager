<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Presentation\Api;

final readonly class CompleteSaleInput
{
    public function __construct(public string $cashSessionId, public PaymentInput $payment, public ?MoneyInput $tenderedAmount = null) {}
}
