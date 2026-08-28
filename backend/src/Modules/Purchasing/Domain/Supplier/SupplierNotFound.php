<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Domain\Supplier;

use RuntimeException;
use Zandu\SharedKernel\Error\ResourceNotFound;
use Zandu\SharedKernel\Identity\SupplierId;

final class SupplierNotFound extends RuntimeException implements ResourceNotFound
{
    public static function withId(SupplierId $supplierId): self
    {
        return new self(sprintf('Supplier "%s" was not found.', $supplierId->toString()));
    }
}
