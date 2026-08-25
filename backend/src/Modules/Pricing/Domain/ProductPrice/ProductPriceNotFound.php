<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Domain\ProductPrice;

use RuntimeException;
use Zandu\SharedKernel\Identity\ProductPriceId;

final class ProductPriceNotFound extends RuntimeException
{
    public static function withId(ProductPriceId $id): self
    {
        return new self(sprintf('Product price "%s" was not found.', $id->toString()));
    }
}
