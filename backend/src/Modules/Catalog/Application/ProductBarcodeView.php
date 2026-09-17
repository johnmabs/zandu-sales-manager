<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application;

final readonly class ProductBarcodeView
{
    public function __construct(
        public string $id,
        public string $productId,
        public string $packagingId,
        public string $barcode,
        public string $status,
        public int $version,
    ) {}
}
