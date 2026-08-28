<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Domain\PurchaseOrder;

use RuntimeException;
use Zandu\SharedKernel\Identity\PurchaseOrderId;

final class PurchaseOrderNotFound extends RuntimeException
{
    public static function withId(PurchaseOrderId $id): self
    {
        return new self(sprintf('Purchase order "%s" was not found.', $id->toString()));
    }
}
