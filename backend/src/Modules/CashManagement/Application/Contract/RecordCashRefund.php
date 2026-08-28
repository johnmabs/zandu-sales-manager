<?php

declare(strict_types=1);

namespace Zandu\Modules\CashManagement\Application\Contract;

use Zandu\SharedKernel\Identity\{ActorId, CashSessionId, OrganizationId, PaymentRefundId, StoreId};
use Zandu\SharedKernel\Money\Money;

final readonly class RecordCashRefund
{
    public function __construct(
        public OrganizationId $organizationId,
        public StoreId $storeId,
        public CashSessionId $cashSessionId,
        public PaymentRefundId $paymentRefundId,
        public Money $amount,
        public ?string $reason,
        public ActorId $actorId,
    ) {}
}
