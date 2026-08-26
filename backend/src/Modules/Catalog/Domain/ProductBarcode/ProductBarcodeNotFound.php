<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Domain\ProductBarcode;

use RuntimeException;
use Zandu\SharedKernel\Error\ResourceNotFound;
use Zandu\SharedKernel\Identity\ProductBarcodeId;

final class ProductBarcodeNotFound extends RuntimeException implements ResourceNotFound
{
    public static function withId(ProductBarcodeId $id): self
    {
        return new self(sprintf('Product barcode "%s" was not found.', $id->toString()));
    }
}
