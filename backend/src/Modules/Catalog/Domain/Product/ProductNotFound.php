<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Domain\Product;

use RuntimeException;
use Zandu\SharedKernel\Error\ResourceNotFound;
use Zandu\SharedKernel\Identity\ProductId;

final class ProductNotFound extends RuntimeException implements ResourceNotFound
{
    public static function withId(ProductId $id): self
    {
        return new self(sprintf('Product "%s" was not found.', $id->toString()));
    }
}
