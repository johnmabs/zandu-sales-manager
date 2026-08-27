<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Application\Contract;

use Zandu\SharedKernel\Identity\{ActorId,OrganizationId,PaymentId,SaleId};
use Zandu\SharedKernel\Money\Money;

interface PaymentRecorder
{
    public function recordCashSale(OrganizationId $organizationId, SaleId $saleId, Money $amount, ActorId $actorId): PaymentId;
}
