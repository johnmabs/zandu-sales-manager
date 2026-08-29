<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Domain\PurchaseReturn;

use RuntimeException;
use Zandu\SharedKernel\Identity\PurchaseReturnId;

final class PurchaseReturnNotFound extends RuntimeException
{
    public static function withId(PurchaseReturnId $id): self
    {
        return new self(sprintf('Purchase return "%s" was not found.', $id->toString()));
    }
}
