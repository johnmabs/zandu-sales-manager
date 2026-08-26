<?php

declare(strict_types=1);

namespace Zandu\Modules\CashManagement\Application\Contract;

use Zandu\SharedKernel\Identity\{ActorId,CashSessionId,OrganizationId,SaleId,StoreId};
use Zandu\SharedKernel\Money\Money;

final readonly class RecordSalePayment
{
    public function __construct(
        public OrganizationId $organizationId,
        public StoreId $storeId,
        public CashSessionId $cashSessionId,
        public SaleId $saleId,
        public Money $amount,
        public ActorId $actorId,
    ) {}
}
