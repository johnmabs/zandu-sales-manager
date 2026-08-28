<?php

declare(strict_types=1);

namespace Zandu\Modules\Payments\Presentation\Api;

final readonly class PaymentRefundInput
{
    public function __construct(
        public string $returnSaleId,
        public string $cashSessionId,
        public string $amount,
        public string $currency,
        public ?string $reason = null,
    ) {}
}
