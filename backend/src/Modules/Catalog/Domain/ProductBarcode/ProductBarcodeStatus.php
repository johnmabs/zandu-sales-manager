<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Domain\ProductBarcode;

enum ProductBarcodeStatus: string
{
    case Active = 'ACTIVE';
    case Removed = 'REMOVED';
}
