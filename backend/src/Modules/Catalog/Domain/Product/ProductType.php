<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Domain\Product;

enum ProductType: string
{
    case Physical = 'PHYSICAL';
    case Service = 'SERVICE';
}
