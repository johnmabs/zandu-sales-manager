<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Domain\Store;

use RuntimeException;
use Zandu\SharedKernel\Identity\StoreId;

final class StoreNotFound extends RuntimeException
{
    public static function withId(StoreId $id): self
    {
        return new self(sprintf('Store "%s" was not found.', $id->toString()));
    }
}
