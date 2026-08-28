<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Application\Contract;

use Zandu\SharedKernel\Identity\{OrganizationId, ReturnSaleId, SaleId, StoreId};
use Zandu\SharedKernel\Money\Money;

final readonly class RefundableReturn
{
    public function __construct(
        public OrganizationId $organizationId,
        public StoreId $storeId,
        public SaleId $saleId,
        public ReturnSaleId $returnSaleId,
        public Money $refundableAmount,
    ) {}
}
