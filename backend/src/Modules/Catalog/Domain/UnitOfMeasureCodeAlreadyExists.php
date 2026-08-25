<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Domain;

use RuntimeException;

final class UnitOfMeasureCodeAlreadyExists extends RuntimeException
{
    public static function withCode(UnitOfMeasureCode $code): self
    {
        return new self(sprintf('Unit of measure code "%s" already exists in this organization.', $code->value()));
    }
}
