<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Domain;

use RuntimeException;
use Zandu\SharedKernel\Identity\UnitOfMeasureId;

final class UnitOfMeasureNotFound extends RuntimeException
{
    public static function withId(UnitOfMeasureId $id): self
    {
        return new self(sprintf('Unit of measure "%s" was not found.', $id->toString()));
    }
}
