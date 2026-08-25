<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Domain\PriceList;

use RuntimeException;
use Zandu\SharedKernel\Identity\PriceListId;

final class PriceListNotFound extends RuntimeException
{
    public static function withId(PriceListId $id): self
    {
        return new self(sprintf('Price list "%s" was not found.', $id->toString()));
    }
}
