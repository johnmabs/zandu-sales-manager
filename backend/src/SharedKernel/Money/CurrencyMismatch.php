<?php

declare(strict_types=1);

namespace Zandu\SharedKernel\Money;

use DomainException;

final class CurrencyMismatch extends DomainException
{
    public static function between(Currency $left, Currency $right): self
    {
        return new self(sprintf(
            'Cannot operate on amounts in different currencies: %s and %s.',
            $left->code(),
            $right->code(),
        ));
    }
}
