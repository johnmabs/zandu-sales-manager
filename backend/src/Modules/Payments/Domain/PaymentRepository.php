<?php

declare(strict_types=1);

namespace Zandu\Modules\Payments\Domain;

use Zandu\SharedKernel\Identity\{OrganizationId, PaymentId, SaleId};

interface PaymentRepository
{
    /** Returns false when the sale payment already exists. */
    public function addConfirmedOnce(Payment $payment): bool;

    public function findConfirmedCashSale(OrganizationId $organizationId, SaleId $saleId): ?Payment;

    public function getForUpdate(OrganizationId $organizationId, PaymentId $paymentId): Payment;
}
