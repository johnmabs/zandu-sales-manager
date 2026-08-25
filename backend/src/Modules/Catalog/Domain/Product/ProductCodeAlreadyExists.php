<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Domain\Product;

use RuntimeException;
use Zandu\SharedKernel\Error\ResourceConflict;

final class ProductCodeAlreadyExists extends RuntimeException implements ResourceConflict
{
    public static function withCode(ProductCode $code): self
    {
        return new self(sprintf('Product code "%s" already exists in this organization.', $code->value()));
    }
}
