<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Presentation\Api;

use Zandu\Modules\Catalog\Application\ProductPackagingView;

final readonly class ProductPackagingResourceFactory
{
    public function fromView(ProductPackagingView $view): ProductPackagingResource
    {
        return new ProductPackagingResource(...get_object_vars($view));
    }
}
