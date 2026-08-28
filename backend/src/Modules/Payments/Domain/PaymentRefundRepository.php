<?php

declare(strict_types=1);

namespace Zandu\Modules\Payments\Domain;

use Zandu\SharedKernel\Idempotency\IdempotencyKey;
use Zandu\SharedKernel\Identity\{OrganizationId, PaymentId, ReturnSaleId};
use Zandu\SharedKernel\Money\{Currency, Money};

interface PaymentRefundRepository
{
    public function add(PaymentRefund $refund): void;

    public function findByIdempotencyKey(OrganizationId $organizationId, PaymentId $paymentId, IdempotencyKey $key): ?PaymentRefund;

    public function confirmedTotalForPayment(OrganizationId $organizationId, PaymentId $paymentId, Currency $currency): Money;

    public function confirmedTotalForReturn(OrganizationId $organizationId, ReturnSaleId $returnSaleId, Currency $currency): Money;
}
