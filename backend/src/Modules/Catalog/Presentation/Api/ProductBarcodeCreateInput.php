<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Presentation\Api;

final readonly class ProductBarcodeCreateInput
{
    public function __construct(public string $barcode) {}
}
