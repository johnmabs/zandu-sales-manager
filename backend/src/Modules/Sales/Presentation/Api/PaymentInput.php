<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Presentation\Api;

final readonly class PaymentInput
{
    public function __construct(public string $method, public MoneyInput $amount) {}
}
