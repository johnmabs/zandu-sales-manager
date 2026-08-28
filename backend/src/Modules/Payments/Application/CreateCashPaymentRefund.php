<?php

declare(strict_types=1);

namespace Zandu\Modules\Payments\Application;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Idempotency\IdempotencyKey;
use Zandu\SharedKernel\Identity\{CashSessionId, PaymentId, ReturnSaleId};
use Zandu\SharedKernel\Money\Money;

final readonly class CreateCashPaymentRefund
{
    public function __construct(
        public PaymentId $paymentId,
        public ReturnSaleId $returnSaleId,
        public CashSessionId $cashSessionId,
        public Money $amount,
        public ?string $reason,
        public IdempotencyKey $idempotencyKey,
        public ActorContext $actor,
    ) {}
}
