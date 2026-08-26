<?php

declare(strict_types=1);

namespace Zandu\Modules\CashManagement\Application\Contract;

use Zandu\SharedKernel\Identity\SaleId;

final readonly class CashSalePaymentResult
{
    public const int CONTRACT_VERSION = 1;

    public function __construct(public SaleId $saleId, public bool $alreadyRecorded = false) {}
}
