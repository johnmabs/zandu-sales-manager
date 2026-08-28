<?php

declare(strict_types=1);

namespace Zandu\Modules\CashManagement\Domain\CashMovement;

enum CashMovementType: string
{
    case CashIn = 'CASH_IN';
    case CashOut = 'CASH_OUT';
    case CashWithdrawal = 'CASH_WITHDRAWAL';
    case SalePayment = 'SALE_PAYMENT';
    case Refund = 'REFUND';
    public function isIn(): bool
    {
        return self::CashIn === $this || self::SalePayment === $this;
    }
}
