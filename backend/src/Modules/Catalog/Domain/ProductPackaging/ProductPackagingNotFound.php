<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Domain\ProductPackaging;

use RuntimeException;
use Zandu\SharedKernel\Identity\ProductPackagingId;

final class ProductPackagingNotFound extends RuntimeException
{
    public static function withId(ProductPackagingId $id): self
    {
        return new self(sprintf('Product packaging "%s" was not found.', $id->toString()));
    }
}
