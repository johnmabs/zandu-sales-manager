<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Domain\StoreClosure;

use RuntimeException;
use Zandu\SharedKernel\Error\ResourceNotFound;
use Zandu\SharedKernel\Identity\StoreId;

final class StoreClosureNotFound extends RuntimeException implements ResourceNotFound
{
    public static function activeForStore(StoreId $storeId): self
    {
        return new self(sprintf('No active closure exists for store "%s".', $storeId->toString()));
    }
}
