<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Domain\Product;

enum ProductStatus: string
{
    case Draft = 'DRAFT';
    case Active = 'ACTIVE';
    case Inactive = 'INACTIVE';
    case Archived = 'ARCHIVED';
}
