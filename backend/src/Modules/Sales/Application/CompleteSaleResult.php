<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Application;

use Zandu\SharedKernel\Identity\{PaymentId,SaleId};
use Zandu\SharedKernel\Money\Money;

final readonly class CompleteSaleResult
{
    public function __construct(public SaleId $saleId, public Money $total, public PaymentId $paymentId, public Money $changeAmount) {}
}
