<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Presentation\Api;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;

#[ApiResource(operations: [new Get(name: 'barcode_resolve', uriTemplate: '/catalog/barcodes/{barcode}', provider: BarcodeResolutionProvider::class)])]
final readonly class BarcodeResolutionResource
{
    public function __construct(public string $productId, public string $packagingId) {}
}
